import { cn } from "@/lib/utils"
import { CheckCircle2, Globe, User } from "lucide-react"

export interface Template {
  id: number
  slug: string
  name: string
  description: string | null
  is_system: boolean
}

interface Props {
  templates: Template[]
  value: string
  onChange: (slug: string) => void
}

export function TemplateSelector({ templates, value, onChange }: Props) {
  const system = templates.filter((t) => t.is_system)
  const custom = templates.filter((t) => !t.is_system)

  return (
    <div className="space-y-4">
      {system.length > 0 && (
        <div className="space-y-2">
          <p className="text-xs font-medium uppercase tracking-widest text-zinc-500">
            System Templates
          </p>
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
            {system.map((t) => (
              <TemplateCard
                key={t.slug}
                template={t}
                selected={value === t.slug}
                onSelect={onChange}
              />
            ))}
          </div>
        </div>
      )}

      {custom.length > 0 && (
        <div className="space-y-2">
          <p className="text-xs font-medium uppercase tracking-widest text-zinc-500">
            My Templates
          </p>
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
            {custom.map((t) => (
              <TemplateCard
                key={t.slug}
                template={t}
                selected={value === t.slug}
                onSelect={onChange}
              />
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function TemplateCard({
  template,
  selected,
  onSelect,
}: {
  template: Template
  selected: boolean
  onSelect: (slug: string) => void
}) {
  return (
    <button
      type="button"
      onClick={() => onSelect(template.slug)}
      className={cn(
        "relative flex flex-col gap-1.5 rounded-xl border p-3.5 text-left transition-all duration-150",
        selected
          ? "border-white bg-white/5 shadow-[0_0_0_1px_white]"
          : "border-zinc-800 bg-zinc-900 hover:border-zinc-600 hover:bg-zinc-800/60",
      )}
    >
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium text-white">{template.name}</span>
        {selected ? (
          <CheckCircle2 className="h-4 w-4 shrink-0 text-white" />
        ) : (
          <div className="h-4 w-4 shrink-0 rounded-full border border-zinc-600" />
        )}
      </div>

      {template.description && (
        <p className="text-xs leading-relaxed text-zinc-400 line-clamp-2">
          {template.description}
        </p>
      )}

      <div className="mt-1 flex items-center gap-1">
        {template.is_system ? (
          <Globe className="h-3 w-3 text-zinc-500" />
        ) : (
          <User className="h-3 w-3 text-zinc-500" />
        )}
        <span className="text-[10px] text-zinc-600">
          {template.is_system ? "System" : "Custom"}
        </span>
      </div>
    </button>
  )
}
