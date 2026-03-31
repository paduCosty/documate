import { cn } from "@/lib/utils"

type BadgeVariant = "default" | "success" | "error" | "pro" | "business" | "new"

interface DocumateBadgeProps {
  variant?: BadgeVariant
  children: React.ReactNode
  className?: string
}

const badgeStyles: Record<BadgeVariant, string> = {
  default: "bg-zinc-800 text-zinc-400",
  success: "bg-[rgba(34,197,94,0.10)] text-[#22c55e]",
  error: "bg-[rgba(239,68,68,0.10)] text-[#ef4444]",
  pro: "bg-white text-black",
  business: "bg-amber-500 text-black",
  new: "bg-blue-500/10 text-blue-400",
}

export function DocumateBadge({ variant = "default", children, className }: DocumateBadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        badgeStyles[variant],
        className
      )}
    >
      {children}
    </span>
  )
}
