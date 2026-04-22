import axios, {
  AxiosError,
  AxiosHeaders,
  type AxiosRequestConfig,
  type AxiosResponse,
  type InternalAxiosRequestConfig
} from 'axios'
import { $t } from '@/locales'
import { useUserStore } from '@/store/modules/user'
import type { BaseResponse } from '@/types'
import { getAccessTokenHeaderValue, getRefreshTokenHeaderValue } from '@/utils/auth/token-storage'
import { HttpError, handleError, showError, showSuccess } from './error'
import { ApiStatus } from './status'

const REQUEST_TIMEOUT = 15000
const LOGOUT_DELAY = 500
const MAX_RETRIES = 0
const RETRY_DELAY = 1000
const UNAUTHORIZED_DEBOUNCE_TIME = 3000

const { VITE_API_URL, VITE_WITH_CREDENTIALS } = import.meta.env

interface ExtendedAxiosRequestConfig<T = any> extends AxiosRequestConfig {
  showErrorMessage?: boolean
  showSuccessMessage?: boolean
  responseAdapter?: (data: any, response: AxiosResponse<BaseResponse<any>>) => T
  skipAuthRefresh?: boolean
  _retry?: boolean
}

interface PendingRequest {
  config: ExtendedAxiosRequestConfig
  resolve: (value: AxiosResponse<BaseResponse<any>>) => void
  reject: (reason?: unknown) => void
}

const createAxiosOptions = (): AxiosRequestConfig => ({
  timeout: REQUEST_TIMEOUT,
  baseURL: VITE_API_URL,
  withCredentials: VITE_WITH_CREDENTIALS === 'true'
})

const axiosInstance = axios.create(createAxiosOptions())
const refreshInstance = axios.create(createAxiosOptions())

let isRefreshing = false
let pendingRequests: PendingRequest[] = []
let isUnauthorizedErrorShown = false
let unauthorizedTimer: NodeJS.Timeout | null = null

axiosInstance.interceptors.request.use(
  (request: InternalAxiosRequestConfig) => {
    const accessToken = getAccessTokenHeaderValue()
    const headers = AxiosHeaders.from(request.headers)

    if (accessToken && !headers.has('Authorization')) {
      headers.set('Authorization', accessToken)
    }

    const method = request.method?.toUpperCase()
    if (
      request.data &&
      !(request.data instanceof FormData) &&
      method &&
      ['POST', 'PUT', 'PATCH'].includes(method)
    ) {
      if (!headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json')
      }
    }

    request.headers = headers
    return request
  },
  (error) => {
    const httpError = createHttpError($t('httpMsg.requestConfigError'), ApiStatus.error)
    showError(httpError)
    return Promise.reject(error)
  }
)

axiosInstance.interceptors.response.use(
  async (response: AxiosResponse<BaseResponse<any>>) => {
    if (isBlobResponse(response)) {
      return response
    }

    const body = response.data
    if (body?.code === ApiStatus.success) {
      return response
    }

    if (body?.code === ApiStatus.unauthorized) {
      return handleUnauthorizedResponse(response)
    }

    throw createHttpError(
      body?.msg || $t('httpMsg.requestFailed'),
      typeof body?.code === 'number' ? body.code : ApiStatus.error,
      {
        data: body?.data,
        url: response.config.url,
        method: response.config.method?.toUpperCase()
      }
    )
  },
  async (error: AxiosError<BaseResponse<any>>) => {
    if (error.response?.status === ApiStatus.unauthorized) {
      return handleUnauthorizedResponse(error.response, error)
    }

    return Promise.reject(handleError(error))
  }
)

function createHttpError(
  message: string,
  code: number,
  options?: {
    data?: unknown
    url?: string
    method?: string
  }
) {
  return new HttpError(message, code, options)
}

function isBlobResponse(response: AxiosResponse) {
  return response.config.responseType === 'blob' || response.data instanceof Blob
}

function createRequestQueue(config: ExtendedAxiosRequestConfig) {
  return new Promise<AxiosResponse<BaseResponse<any>>>((resolve, reject) => {
    pendingRequests.push({
      config,
      resolve,
      reject
    })
  })
}

function flushPendingRequests(error?: unknown, accessToken?: string) {
  const queue = [...pendingRequests]
  pendingRequests = []

  queue.forEach((item) => {
    if (error || !accessToken) {
      item.reject(error)
      return
    }

    retryRequestWithToken(item.config, accessToken).then(item.resolve).catch(item.reject)
  })
}

function retryRequestWithToken(
  config: ExtendedAxiosRequestConfig,
  accessToken: string
): Promise<AxiosResponse<BaseResponse<any>>> {
  const headers = AxiosHeaders.from((config.headers ?? {}) as any)
  headers.set('Authorization', accessToken)

  const retryConfig: ExtendedAxiosRequestConfig = {
    ...config,
    headers
  }

  retryConfig._retry = true

  return axiosInstance.request<BaseResponse<any>>(retryConfig as AxiosRequestConfig)
}

async function refreshAccessToken() {
  const userStore = useUserStore()
  const currentRefreshToken = getRefreshTokenHeaderValue()

  if (!currentRefreshToken) {
    throw createHttpError($t('httpMsg.unauthorized'), ApiStatus.unauthorized)
  }

  const response = await refreshInstance.request<BaseResponse<Api.Auth.LoginResponse>>({
    url: '/refreshToken',
    method: 'GET',
    headers: {
      Authorization: currentRefreshToken
    }
  })

  const body = response.data
  const accessToken = body?.data?.access_token

  if (body?.code !== ApiStatus.success || !accessToken) {
    throw createHttpError(body?.msg || $t('httpMsg.unauthorized'), ApiStatus.unauthorized, {
      data: body?.data,
      url: response.config.url,
      method: response.config.method?.toUpperCase()
    })
  }

  userStore.setToken(body.data.access_token, body.data.refresh_token)
  return getAccessTokenHeaderValue() || userStore.accessToken
}

function resetUnauthorizedState() {
  isUnauthorizedErrorShown = false
  if (unauthorizedTimer) {
    clearTimeout(unauthorizedTimer)
  }
  unauthorizedTimer = null
}

function triggerLogout(error: HttpError) {
  if (isUnauthorizedErrorShown) {
    return
  }

  isUnauthorizedErrorShown = true
  unauthorizedTimer = setTimeout(resetUnauthorizedState, UNAUTHORIZED_DEBOUNCE_TIME)

  showError(error, true)
  setTimeout(() => {
    useUserStore().logOut()
  }, LOGOUT_DELAY)
}

function rejectUnauthorized(message?: string) {
  const error = createHttpError(message || $t('httpMsg.unauthorized'), ApiStatus.unauthorized)
  triggerLogout(error)
  return Promise.reject(error)
}

async function handleUnauthorizedResponse(
  response: AxiosResponse<BaseResponse<any>>,
  error?: AxiosError<BaseResponse<any>>
) {
  const config = response.config as ExtendedAxiosRequestConfig
  const message = response.data?.msg || error?.message || $t('httpMsg.unauthorized')

  if (config.skipAuthRefresh || config._retry) {
    return rejectUnauthorized(message)
  }

  if (!getRefreshTokenHeaderValue()) {
    return rejectUnauthorized(message)
  }

  const pendingRequest = createRequestQueue(config)

  if (!isRefreshing) {
    isRefreshing = true

    refreshAccessToken()
      .then((accessToken) => {
        flushPendingRequests(undefined, accessToken)
      })
      .catch((refreshError) => {
        const unauthorizedError =
          refreshError instanceof HttpError
            ? refreshError
            : createHttpError(message, ApiStatus.unauthorized)

        flushPendingRequests(unauthorizedError)
        triggerLogout(unauthorizedError)
      })
      .finally(() => {
        isRefreshing = false
      })
  }

  return pendingRequest
}

function shouldRetry(statusCode: number) {
  return [
    ApiStatus.requestTimeout,
    ApiStatus.internalServerError,
    ApiStatus.badGateway,
    ApiStatus.serviceUnavailable,
    ApiStatus.gatewayTimeout
  ].includes(statusCode)
}

function delay(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

async function requestWithRetry<T>(
  config: ExtendedAxiosRequestConfig<T>,
  retries: number = MAX_RETRIES
): Promise<T> {
  try {
    return await request<T>(config)
  } catch (error) {
    if (retries > 0 && error instanceof HttpError && shouldRetry(error.code)) {
      await delay(RETRY_DELAY)
      return requestWithRetry<T>(config, retries - 1)
    }

    throw error
  }
}

async function request<T = any>(config: ExtendedAxiosRequestConfig<T>): Promise<T> {
  const method = config.method?.toUpperCase() || 'GET'

  if (['POST', 'PUT', 'PATCH'].includes(method) && config.params && !config.data) {
    config.data = config.params
    config.params = undefined
  }

  try {
    const response = await axiosInstance.request<BaseResponse<any>>(config)

    if (isBlobResponse(response)) {
      return response.data as T
    }

    const body = response.data
    const data = config.responseAdapter ? config.responseAdapter(body.data, response) : body.data

    if (config.showSuccessMessage && body.msg) {
      showSuccess(body.msg)
    }

    return data as T
  } catch (error) {
    if (error instanceof HttpError && error.code !== ApiStatus.unauthorized) {
      showError(error, config.showErrorMessage !== false)
    }

    return Promise.reject(error)
  }
}

const api = {
  get<T>(config: ExtendedAxiosRequestConfig<T>) {
    return requestWithRetry<T>({ ...config, method: 'GET' })
  },
  post<T>(config: ExtendedAxiosRequestConfig<T>) {
    return requestWithRetry<T>({ ...config, method: 'POST' })
  },
  put<T>(config: ExtendedAxiosRequestConfig<T>) {
    return requestWithRetry<T>({ ...config, method: 'PUT' })
  },
  del<T>(config: ExtendedAxiosRequestConfig<T>) {
    return requestWithRetry<T>({ ...config, method: 'DELETE' })
  },
  request<T>(config: ExtendedAxiosRequestConfig<T>) {
    return requestWithRetry<T>(config)
  }
}

export default api
