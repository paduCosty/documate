import { forwardRef } from "react"
import { Loader2 } from "lucide-react"
import { cn } from "@/lib/utils"

type ButtonVariant = "primary" | "ghost" | "outline" | "destructive"
type ButtonSize = "sm" | "md" | "lg"

interface DocumateButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  size?: ButtonSize
  loading?: boolean
  children: React.ReactNode
}

const variantStyles: Record<ButtonVariant, string> = {
  primary: "bg-white text-black hover:bg-zinc-100 shadow-[0_0_0_1px_rgba(255,255,255,0.08),0_2px_8px_rgba(0,0,0,0.3)]",
  ghost: "text-zinc-400 hover:text-white hover:bg-zinc-800/50",
  outline: "border border-zinc-700 text-zinc-300 hover:border-zinc-500 hover:text-white",
  destructive: "bg-red-950/50 border border-red-900 text-red-400 hover:bg-red-950",
}

const sizeStyles: Record<ButtonSize, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-4 py-2 text-sm",
  lg: "px-6 py-3 text-base",
}

export const DocumateButton = forwardRef<HTMLButtonElement, DocumateButtonProps>(
  ({ variant = "primary", size = "md", loading = false, disabled, children, className, ...props }, ref) => {
    return (
      <button
        ref={ref}
        disabled={disabled || loading}
        className={cn(
          "inline-flex items-center justify-center gap-2 rounded-xl font-medium transition-all duration-150",
          "disabled:opacity-40 disabled:cursor-not-allowed",
          "focus-visible:ring-2 focus-visible:ring-white/20 focus-visible:outline-none",
          variantStyles[variant],
          sizeStyles[size],
          className
        )}
        {...props}
      >
        {loading && <Loader2 className="h-4 w-4 animate-spin" />}
        {children}
      </button>
    )
  }
)

DocumateButton.displayName = "DocumateButton"
