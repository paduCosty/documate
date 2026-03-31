"use client"

import { useState } from "react"
import { Search, FileText, Download, Trash2, ChevronLeft, ChevronRight, FolderOpen } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import { Link } from "@inertiajs/react"

const files = [
  { id: 1, name: "contract-final.pdf", tool: "Merge", toolColor: "bg-blue-500/20 text-blue-400", originalSize: "4.8 MB", resultSize: "2.4 MB", reduction: "50%", date: "Mar 28, 2026", expires: "22h remaining" },
  { id: 2, name: "presentation-compressed.pdf", tool: "Compress", toolColor: "bg-green-500/20 text-green-400", originalSize: "5.2 MB", resultSize: "1.1 MB", reduction: "79%", date: "Mar 28, 2026", expires: "20h remaining" },
  { id: 3, name: "report-2024.pdf", tool: "Convert", toolColor: "bg-purple-500/20 text-purple-400", originalSize: "3.8 MB", resultSize: "3.8 MB", reduction: null, date: "Mar 27, 2026", expires: "4h remaining" },
  { id: 4, name: "invoice-march.pdf", tool: "Split", toolColor: "bg-orange-500/20 text-orange-400", originalSize: "2.0 MB", resultSize: "0.5 MB", reduction: "75%", date: "Mar 27, 2026", expires: "2h remaining" },
  { id: 5, name: "brochure-pages.zip", tool: "PDF to JPG", toolColor: "bg-pink-500/20 text-pink-400", originalSize: "4.1 MB", resultSize: "8.2 MB", reduction: null, date: "Mar 26, 2026", expires: "Expired", isExpired: true },
  { id: 6, name: "annual-report.pdf", tool: "Merge", toolColor: "bg-blue-500/20 text-blue-400", originalSize: "12.0 MB", resultSize: "6.1 MB", reduction: "49%", date: "Mar 25, 2026", expires: "Expired", isExpired: true },
  { id: 7, name: "slides-deck.pdf", tool: "Convert", toolColor: "bg-purple-500/20 text-purple-400", originalSize: "8.5 MB", resultSize: "8.5 MB", reduction: null, date: "Mar 24, 2026", expires: "Expired", isExpired: true },
]

const toolOptions = ["All Tools", "Merge", "Compress", "Convert", "Split", "PDF to JPG"]
const dateOptions = ["All time", "Today", "This week", "This month"]

export default function FilesPage() {
  const [searchQuery, setSearchQuery] = useState("")
  const [selectedTool, setSelectedTool] = useState("All Tools")
  const [selectedDate, setSelectedDate] = useState("All time")
  const [selectedFiles, setSelectedFiles] = useState<number[]>([])

  const filteredFiles = files.filter((file) => {
    const matchesSearch = file.name.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesTool = selectedTool === "All Tools" || file.tool === selectedTool
    return matchesSearch && matchesTool
  })

  const toggleFile = (id: number) => {
    setSelectedFiles((prev) =>
      prev.includes(id) ? prev.filter((f) => f !== id) : [...prev, id]
    )
  }

  const toggleAll = () => {
    if (selectedFiles.length === filteredFiles.length) {
      setSelectedFiles([])
    } else {
      setSelectedFiles(filteredFiles.map((f) => f.id))
    }
  }

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">My Files</h2>

        {/* Filters */}
        <div className="mt-6 flex flex-wrap gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
            <input
              type="text"
              placeholder="Search files..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full min-w-[200px] rounded-xl border border-zinc-800 bg-zinc-900 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-zinc-500 focus:border-zinc-600 focus:outline-none"
            />
          </div>
          <select
            value={selectedTool}
            onChange={(e) => setSelectedTool(e.target.value)}
            className="rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white focus:border-zinc-600 focus:outline-none"
          >
            {toolOptions.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
          <select
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
            className="rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white focus:border-zinc-600 focus:outline-none"
          >
            {dateOptions.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        </div>

        {/* Bulk Action Bar */}
        {selectedFiles.length > 0 && (
          <div className="mt-4 flex items-center justify-between rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-2.5 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
            <span className="text-sm text-zinc-400">{selectedFiles.length} files selected</span>
            <div className="flex gap-2">
              <DocumateButton variant="outline" size="sm">
                <Download className="h-3.5 w-3.5" />
                Download all
              </DocumateButton>
              <DocumateButton variant="destructive" size="sm">
                <Trash2 className="h-3.5 w-3.5" />
                Delete
              </DocumateButton>
            </div>
          </div>
        )}

        {/* Files Table */}
        {filteredFiles.length > 0 ? (
          <DocumateCard className="mt-6 overflow-hidden p-0">
            {/* Table Header */}
            <div className="grid grid-cols-[40px_1fr_100px_90px_90px_70px_100px_100px_50px] gap-4 bg-zinc-800/50 px-6 py-3 text-xs font-medium text-zinc-500">
              <div>
                <input
                  type="checkbox"
                  checked={selectedFiles.length === filteredFiles.length}
                  onChange={toggleAll}
                  className="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-white focus:ring-0 focus:ring-offset-0"
                />
              </div>
              <span>File</span>
              <span>Tool</span>
              <span>Original</span>
              <span>Result</span>
              <span>Saved</span>
              <span>Date</span>
              <span>Expires</span>
              <span></span>
            </div>

            {/* Table Rows */}
            {filteredFiles.map((file) => (
              <div
                key={file.id}
                className="grid grid-cols-[40px_1fr_100px_90px_90px_70px_100px_100px_50px] gap-4 border-t border-zinc-800 px-6 py-4 transition-colors hover:bg-zinc-800/30"
              >
                <div>
                  <input
                    type="checkbox"
                    checked={selectedFiles.includes(file.id)}
                    onChange={() => toggleFile(file.id)}
                    className="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-white focus:ring-0 focus:ring-offset-0"
                  />
                </div>
                <div className="flex items-center gap-3">
                  <FileText className="h-3.5 w-3.5 flex-shrink-0 text-red-400" />
                  <span className="truncate text-sm text-white">{file.name}</span>
                </div>
                <span className={`inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs ${file.toolColor}`}>
                  {file.tool}
                </span>
                <span className="font-mono text-xs text-zinc-500">{file.originalSize}</span>
                <span className="font-mono text-xs text-zinc-500">{file.resultSize}</span>
                {file.reduction ? (
                  <DocumateBadge variant="success">{file.reduction}</DocumateBadge>
                ) : (
                  <span className="text-xs text-zinc-600">-</span>
                )}
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
        ) : (
          /* Empty State */
          <div className="mt-16 flex flex-col items-center justify-center text-center">
            <FolderOpen className="h-12 w-12 text-zinc-700" />
            <h3 className="mt-4 text-lg font-medium text-white">No files yet</h3>
            <p className="mt-2 text-sm text-zinc-500">Use a tool to get started</p>
            <Link href="/tools" className="mt-4">
              <DocumateButton variant="outline" size="sm">Browse tools</DocumateButton>
            </Link>
          </div>
        )}

        {/* Pagination */}
        {filteredFiles.length > 0 && (
          <DocumateCard padding="sm" className="mt-6">
            <div className="flex items-center justify-between">
              <span className="text-sm text-zinc-500">Showing 1-{filteredFiles.length} of {files.length} files</span>
              <div className="flex items-center gap-2">
                <button className="rounded-lg border border-zinc-700 p-1.5 text-zinc-500 transition-colors hover:border-zinc-500 hover:text-white disabled:opacity-40" disabled>
                  <ChevronLeft className="h-4 w-4" />
                </button>
                <span className="rounded-lg bg-zinc-700 px-3 py-1 text-sm text-white">1</span>
                <button className="rounded-lg border border-zinc-700 p-1.5 text-zinc-500 transition-colors hover:border-zinc-500 hover:text-white disabled:opacity-40" disabled>
                  <ChevronRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </DocumateCard>
        )}
      </div>
    </AppLayout>
  )
}
