import type { AuthDirective, RippleDirective, HighlightDirective } from '@/directives'

declare module 'vue' {
  export interface GlobalDirectives {
    /** Button permission directive; role directive was removed with role management. */
    vAuth: AuthDirective
    vRipple: RippleDirective
    vHighlight: HighlightDirective
  }
}
