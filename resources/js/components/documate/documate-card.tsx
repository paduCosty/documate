import { cn } from "@/lib/utils"

type CardPadding = "sm" | "md" | "lg"

interface DocumateCardProps {
  children: React.ReactNode
  className?: string
  padding?: CardPadding
  hover?: boolean
}

const paddingStyles: Record<CardPadding, string> = {
  sm: "p-4",
  md: "p-6",
  lg: "p-8",
}

export function DocumateCard({ children, className, padding = "md", hover = false }: DocumateCardProps) {
  return (
    <div
      className={cn(
        "rounded-2xl border border-zinc-800 bg-zinc-900 shadow-[0_0_0_1px_#27272a,0_4px_24px_rgba(0,0,0,0.3)]",
        paddingStyles[padding],
        hover && "transition-all duration-150 hover:translate-y-[-2px] hover:border-zinc-700 hover:shadow-[0_0_0_1px_#3f3f46,0_8px_32px_rgba(0,0,0,0.4)]",
        className
      )}
    >
      {children}
    </div>
  )
}
