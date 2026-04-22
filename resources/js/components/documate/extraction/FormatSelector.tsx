import { cn } from "@/lib/utils"
import { FileSpreadsheet, FileText, Braces } from "lucide-react"

export interface OutputFormat {
  value: string
  label: string
  mime: string
}

interface Props {
  formats: OutputFormat[]
  value: string
  onChange: (format: string) => void
}

const formatIcons: Record<string, React.ReactNode> = {
  excel: <FileSpreadsheet className="h-4 w-4" />,
  csv:   <FileText className="h-4 w-4" />,
  json:  <Braces className="h-4 w-4" />,
}

const formatDescriptions: Record<string, string> = {
  excel: "Multi-sheet workbook with formatted tables",
  csv:   "Plain text, compatible with any spreadsheet",
  json:  "Raw structured data with metadata",
}

export function FormatSelector({ formats, value, onChange }: Props) {
  return (
    <div className="flex gap-2">
      {formats.map((f) => (
        <button
          key={f.value}
          type="button"
          onClick={() => onChange(f.value)}
          className={cn(
            "flex flex-1 flex-col items-center gap-1.5 rounded-xl border py-3 px-2 text-center transition-all duration-150",
            value === f.value
              ? "border-white bg-white/5 text-white shadow-[0_0_0_1px_white]"
              : "border-zinc-800 bg-zinc-900 text-zinc-400 hover:border-zinc-600 hover:text-zinc-200",
          )}
        >
          <span className="flex items-center justify-center">
            {formatIcons[f.value] ?? <FileText className="h-4 w-4" />}
          </span>
          <span className="text-xs font-semibold tracking-wide">{f.label}</span>
          <span className="hidden text-[10px] text-zinc-500 sm:block">
            {formatDescriptions[f.value] ?? ""}
          </span>
        </button>
      ))}
    </div>
  )
}
