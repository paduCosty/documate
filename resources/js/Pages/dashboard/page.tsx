import { Link } from "@inertiajs/react"
import { Zap, Files, HardDrive, CreditCard, FileText, Download, FolderOpen } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"

const stats = [
  { icon: Zap, label: "Operations today", value: "3 / 10", subtext: "7 remaining", color: "text-zinc-600" },
  { icon: Files, label: "Files processed", value: "47", subtext: "this month", color: "text-zinc-600" },
  { icon: HardDrive, label: "Storage used", value: "24 MB", subtext: "of 100 MB", color: "text-zinc-600" },
  { icon: CreditCard, label: "Current plan", value: "Free", subtext: "Upgrade →", isLink: true, color: "text-zinc-600" },
]

const recentFiles = [
  { id: 1, name: "contract-final.pdf", tool: "Merge", toolColor: "bg-blue-500/20 text-blue-400", size: "2.4 MB", date: "Today", expires: "22h remaining" },
  { id: 2, name: "presentation-compressed.pdf", tool: "Compress", toolColor: "bg-green-500/20 text-green-400", size: "1.1 MB", date: "Today", expires: "20h remaining" },
  { id: 3, name: "report-2024.pdf", tool: "Convert", toolColor: "bg-purple-500/20 text-purple-400", size: "3.8 MB", date: "Yesterday", expires: "4h remaining" },
  { id: 4, name: "invoice-march.pdf", tool: "Split", toolColor: "bg-orange-500/20 text-orange-400", size: "0.5 MB", date: "Yesterday", expires: "2h remaining" },
  { id: 5, name: "brochure-pages.zip", tool: "PDF to JPG", toolColor: "bg-pink-500/20 text-pink-400", size: "8.2 MB", date: "2 days ago", expires: "Expired", isExpired: true },
]

export default function DashboardPage() {
  return (
    <AppLayout>
      <div className="px-8 py-10">
        {/* Header */}
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-semibold text-white">Good morning, Alex</h2>
          <div className="flex items-center gap-3">
            <DocumateBadge>Free</DocumateBadge>
            <Link href="/dashboard/billing">
              <DocumateButton variant="outline" size="sm">Upgrade</DocumateButton>
            </Link>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
          {stats.map((stat) => (
            <DocumateCard key={stat.label} padding="sm" hover>
              <div className="flex items-start justify-between">
                <stat.icon className={`h-5 w-5 ${stat.color}`} />
              </div>
              <p className="mt-3 text-xs text-zinc-500">{stat.label}</p>
              <p className="mt-1 text-xl font-semibold text-white">{stat.value}</p>
              {stat.isLink ? (
                <Link href="/dashboard/billing" className="text-xs text-zinc-400 transition-colors hover:text-white">
                  {stat.subtext}
                </Link>
              ) : (
                <p className="text-xs text-zinc-500">{stat.subtext}</p>
              )}
            </DocumateCard>
          ))}
        </div>

        {/* Operations Usage Card */}
        <DocumateCard className="mt-4">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-white">Daily operations</span>
            <span className="text-sm text-zinc-500">3 of 10 used</span>
          </div>
          <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800">
            <div className="h-full w-[30%] rounded-full bg-white" />
          </div>
          <p className="mt-2 text-xs text-zinc-600">Resets at midnight UTC</p>
        </DocumateCard>

        {/* Recent Files */}
        <div className="mt-8">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-white">Recent files</h3>
            <Link href="/dashboard/files" className="text-sm text-zinc-500 transition-colors hover:text-white">
              View all &rarr;
            </Link>
          </div>

          <DocumateCard className="mt-4 overflow-hidden p-0">
            {/* Table Header */}
            <div className="grid grid-cols-[1fr_100px_80px_100px_100px_50px] gap-4 bg-zinc-800/50 px-6 py-3 text-xs font-medium text-zinc-500">
              <span>File</span>
              <span>Tool</span>
              <span>Size</span>
              <span>Date</span>
              <span>Expires</span>
              <span></span>
            </div>

            {/* Table Rows */}
            {recentFiles.map((file) => (
              <div
                key={file.id}
                className="grid grid-cols-[1fr_100px_80px_100px_100px_50px] gap-4 border-t border-zinc-800 px-6 py-4 transition-colors hover:bg-zinc-800/30"
              >
                <div className="flex items-center gap-3">
                  <FileText className="h-3.5 w-3.5 flex-shrink-0 text-red-400" />
                  <span className="truncate text-sm text-white">{file.name}</span>
                </div>
                <span className={`inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs ${file.toolColor}`}>
                  {file.tool}
                </span>
                <span className="font-mono text-xs text-zinc-500">{file.size}</span>
                <span className="text-xs text-zinc-500">{file.date}</span>
                <span className={`text-xs ${file.isExpired ? "text-red-400" : "text-zinc-600"}`}>
                  {file.expires}
                </span>
                <div>
                  {!file.isExpired && (
                    <button className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white">
                      <Download className="h-4 w-4" />
                    </button>
                  )}
                </div>
              </div>
            ))}
          </DocumateCard>
        </div>

        {/* Upgrade Banner */}
        <DocumateCard className="mt-8 border-zinc-700">
          <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div className="flex items-start gap-4">
              <div className="rounded-xl bg-amber-500/10 p-2">
                <Zap className="h-5 w-5 text-amber-400" />
              </div>
              <div>
                <h4 className="font-semibold text-white">Unlock unlimited PDF processing</h4>
                <p className="mt-1 text-sm text-zinc-500">
                  Get unlimited operations, larger files, and access to Sign PDF and OCR.
                </p>
              </div>
            </div>
            <Link href="/dashboard/billing">
              <DocumateButton>Upgrade to Pro</DocumateButton>
            </Link>
          </div>
        </DocumateCard>
      </div>
    </AppLayout>
  )
}
