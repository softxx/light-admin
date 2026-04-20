export interface BaseResponse<T = unknown> {
  code: number
  msg: string
  time?: number
  data: T
}
