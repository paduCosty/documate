import { forwardRef } from "react"
import { cn } from "@/lib/utils"

interface DocumateInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  helper?: string
}

export const DocumateInput = forwardRef<HTMLInputElement, DocumateInputProps>(
  ({ label, error, helper, className, ...props }, ref) => {
    return (
      <div className="space-y-1.5">
        {label && (
          <label className="block text-xs font-medium text-zinc-500">
            {label}
          </label>
        )}
        <input
          ref={ref}
          className={cn(
            "w-full rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600",
            "transition-colors focus:border-zinc-600 focus:outline-none focus:ring-0",
            error && "border-red-900 bg-red-950/20",
            className
          )}
          {...props}
        />
        {error && <p className="text-xs text-red-400">{error}</p>}
        {helper && !error && <p className="text-xs text-zinc-500">{helper}</p>}
      </div>
    )
  }
)

DocumateInput.displayName = "DocumateInput"
