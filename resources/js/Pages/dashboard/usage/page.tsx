import { useState } from "react"
import { usePage } from "@inertiajs/react"
import { GitMerge, Minimize2, FileText, Scissors, Image, Presentation, Table, Zap, HardDrive, Files, Activity } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"

const TOOL_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
  "merge_pdf":    GitMerge,
  "compress_pdf": Minimize2,
  "split_pdf":    Scissors,
  "word-to-pdf":  FileText,
  "excel-to-pdf": Table,
  "ppt-to-pdf":   Presentation,
  "pdf-to-jpg":   Image,
}

const PERIODS = [
  { key: "this_month",    label: "This month" },
  { key: "last_month",    label: "Last month" },
  { key: "last_3_months", label: "Last 3 months" },
]

function formatBytes(bytes: number): string {
  if (bytes < 1024)        return bytes + " B"
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB"
  return (bytes / (1024 * 1024)).toFixed(1) + " MB"
}

type Tool = { type: string; label: string; color: string; count: number }
type PeriodData = { totalFiles: number; totalBytes: number; tools: Tool[] }

type Props = {
  periods: Record<string, PeriodData>
  limits: { operations_per_day: number; total_bytes_per_day: number; max_file_size_mb: number }
  todayOps: number
  storageBytes: number
  isPro: boolean
}

export default function UsagePage() {
  const { periods, limits, todayOps, storageBytes, isPro } = usePage<{ props: Props }>().props as unknown as Props

  const [selectedPeriod, setSelectedPeriod] = useState("this_month")

  const data = periods[selectedPeriod] ?? { totalFiles: 0, totalBytes: 0, tools: [] }
  const maxCount = Math.max(...data.tools.map(t => t.count), 1)

  const opsLimit   = limits.operations_per_day
  const opsPercent = isPro ? 0 : Math.min((todayOps / opsLimit) * 100, 100)

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Usage</h2>

        {/* Period Selector */}
        <div className="mt-6 flex gap-2">
          {PERIODS.map((p) => (
            <button
              key={p.key}
              onClick={() => setSelectedPeriod(p.key)}
              className={`rounded-xl px-4 py-2 text-sm transition-colors ${
                selectedPeriod === p.key
                  ? "bg-zinc-800 text-white"
                  : "text-zinc-500 hover:text-white"
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>

        {/* Usage Cards */}
        <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">

          {/* Daily Operations */}
          <DocumateCard>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <Zap className="h-4 w-4 text-zinc-500" />
                <h3 className="font-semibold text-white">Daily Operations</h3>
              </div>
              {isPro && <span className="text-xs text-green-400">Unlimited</span>}
            </div>
            <div className="mt-4 flex items-baseline gap-1">
              <span className="text-3xl font-bold text-white">{todayOps}</span>
              {!isPro && <span className="text-lg text-zinc-500">/ {opsLimit}</span>}
            </div>
            {!isPro && (
              <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-800">
                <div className="h-full rounded-full bg-white transition-all duration-500" style={{ width: `${opsPercent}%` }} />
              </div>
            )}
            <p className="mt-3 text-xs text-zinc-600">Resets at midnight UTC</p>
          </DocumateCard>

          {/* Files Processed */}
          <DocumateCard>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <Files className="h-4 w-4 text-zinc-500" />
                <h3 className="font-semibold text-white">Files Processed</h3>
              </div>
            </div>
            <div className="mt-4 flex items-baseline gap-1">
              <span className="text-3xl font-bold text-white">{data.totalFiles}</span>
              <span className="text-lg text-zinc-500">files</span>
            </div>
            <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-800">
              <div className="h-full rounded-full bg-white transition-all duration-500" style={{ width: data.totalFiles > 0 ? "100%" : "0%" }} />
            </div>
            <p className="mt-3 text-xs text-zinc-600">In selected period</p>
          </DocumateCard>

          {/* Storage Used */}
          <DocumateCard>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <HardDrive className="h-4 w-4 text-zinc-500" />
                <h3 className="font-semibold text-white">Storage Used</h3>
              </div>
            </div>
            <div className="mt-4 flex items-baseline gap-1">
              <span className="text-3xl font-bold text-white">{formatBytes(storageBytes)}</span>
            </div>
            <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-800">
              <div className="h-full rounded-full bg-white transition-all duration-500"
                style={{ width: `${Math.min((storageBytes / (limits.max_file_size_mb * 1024 * 1024 * 10)) * 100, 100)}%` }} />
            </div>
            <p className="mt-3 text-xs text-zinc-600">Total across all files</p>
          </DocumateCard>

          {/* Data Processed */}
          <DocumateCard>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <Activity className="h-4 w-4 text-zinc-500" />
                <h3 className="font-semibold text-white">Data Processed</h3>
              </div>
            </div>
            <div className="mt-4 flex items-baseline gap-1">
              <span className="text-3xl font-bold text-white">{formatBytes(data.totalBytes)}</span>
            </div>
            <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-800">
              <div className="h-full rounded-full bg-white transition-all duration-500"
                style={{ width: data.totalBytes > 0 ? "100%" : "0%" }} />
            </div>
            <p className="mt-3 text-xs text-zinc-600">Input data in selected period</p>
          </DocumateCard>

        </div>

        {/* Operations by Tool */}
        <DocumateCard className="mt-4">
          <h3 className="font-semibold text-white">Operations by tool</h3>
          <div className="mt-6 space-y-5">
            {data.tools.map((tool) => {
              const Icon = TOOL_ICONS[tool.type] ?? FileText
              return (
                <div key={tool.type} className="flex items-center gap-4">
                  <div className="flex w-36 items-center gap-2 flex-shrink-0">
                    <Icon className="h-4 w-4 text-zinc-500 flex-shrink-0" />
                    <span className="text-sm text-zinc-400 truncate">{tool.label}</span>
                  </div>
                  <div className="flex-1">
                    <div className="h-1.5 overflow-hidden rounded-full bg-zinc-800">
                      <div
                        className={`h-full rounded-full transition-all duration-500 ${tool.color}`}
                        style={{ width: `${(tool.count / maxCount) * 100}%` }}
                      />
                    </div>
                  </div>
                  <span className="w-8 text-right text-sm text-zinc-400 flex-shrink-0">{tool.count}</span>
                </div>
              )
            })}
          </div>
        </DocumateCard>

      </div>
    </AppLayout>
  )
}
