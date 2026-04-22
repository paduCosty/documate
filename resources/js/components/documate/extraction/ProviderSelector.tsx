import { cn } from "@/lib/utils"
import { Cpu } from "lucide-react"

export interface AiProvider {
  slug: string
  name: string
  enabled: boolean
}

interface Props {
  providers: AiProvider[]
  value: string | null
  onChange: (slug: string | null) => void
}

export function ProviderSelector({ providers, value, onChange }: Props) {
  const enabled = providers.filter((p) => p.enabled)

  if (enabled.length <= 1) return null

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2">
        <Cpu className="h-3.5 w-3.5 text-zinc-500" />
        <span className="text-xs font-medium uppercase tracking-widest text-zinc-500">
          AI Provider
        </span>
      </div>

      <div className="flex flex-wrap gap-2">
        <ProviderPill
          label="Auto (recommended)"
          selected={value === null}
          onClick={() => onChange(null)}
        />
        {enabled.map((p) => (
          <ProviderPill
            key={p.slug}
            label={p.name}
            selected={value === p.slug}
            onClick={() => onChange(p.slug)}
          />
        ))}
      </div>
    </div>
  )
}

function ProviderPill({
  label,
  selected,
  onClick,
}: {
  label: string
  selected: boolean
  onClick: () => void
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "rounded-full border px-3 py-1 text-xs font-medium transition-all duration-150",
        selected
          ? "border-white bg-white text-black"
          : "border-zinc-700 text-zinc-400 hover:border-zinc-500 hover:text-white",
      )}
    >
      {label}
    </button>
  )
}
