"use client"

import { useState } from "react"
import { Search, FileText, Download, ChevronLeft, ChevronRight, FolderOpen } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import { Link, usePage } from "@inertiajs/react"
import { route } from "ziggy-js"

interface FileItem {
  id: number
  uuid: string
  name: string
  tool: string
  status: string
  inputSize: number
  outputSize: number | null
  date: string
  expires: string
  isExpired: boolean
  canDownload: boolean
}

interface PaginatedFiles {
  data: FileItem[]
  current_page: number
  last_page: number
  total: number
  per_page: number
  next_page_url: string | null
  prev_page_url: string | null
}

function formatSize(bytes: number | null): string {
  if (!bytes) return "—"
  if (bytes < 1024) return bytes + " B"
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB"
  return (bytes / (1024 * 1024)).toFixed(1) + " MB"
}

function getToolLabel(tool: string | null): string {
  const map: Record<string, string> = {
    "merge_pdf": "Merge",
    "merge-pdf": "Merge",
    "compress_pdf": "Compress",
    "compress-pdf": "Compress",
    "split_pdf": "Split",
    "split-pdf": "Split",
    "word_to_pdf": "Word→PDF",
    "word-to-pdf": "Word→PDF",
    "excel_to_pdf": "Excel→PDF",
    "excel-to-pdf": "Excel→PDF",
    "ppt_to_pdf": "PPT→PDF",
    "ppt-to-pdf": "PPT→PDF",
    "pdf_to_jpg": "PDF→JPG",
    "pdf-to-jpg": "PDF→JPG",
  }
  return tool ? (map[tool] ?? tool) : "Unknown"
}

function getToolColor(tool: string | null): string {
  const map: Record<string, string> = {
    "merge_pdf": "bg-blue-500/20 text-blue-400",
    "merge-pdf": "bg-blue-500/20 text-blue-400",
    "compress_pdf": "bg-green-500/20 text-green-400",
    "compress-pdf": "bg-green-500/20 text-green-400",
    "split_pdf": "bg-orange-500/20 text-orange-400",
    "split-pdf": "bg-orange-500/20 text-orange-400",
    "word_to_pdf": "bg-red-500/20 text-red-400",
    "word-to-pdf": "bg-red-500/20 text-red-400",
    "excel_to_pdf": "bg-yellow-500/20 text-yellow-400",
    "excel-to-pdf": "bg-yellow-500/20 text-yellow-400",
    "ppt_to_pdf": "bg-orange-500/20 text-orange-400",
    "ppt-to-pdf": "bg-orange-500/20 text-orange-400",
    "pdf_to_jpg": "bg-pink-500/20 text-pink-400",
    "pdf-to-jpg": "bg-pink-500/20 text-pink-400",
  }
  return tool ? (map[tool] ?? "bg-zinc-500/20 text-zinc-400") : "bg-zinc-500/20 text-zinc-400"
}

const toolOptions = ["All Tools", "Merge", "Compress", "Split", "Word→PDF", "Excel→PDF", "PPT→PDF", "PDF→JPG"]

export default function FilesPage() {
  const { files } = usePage<{ files: PaginatedFiles }>().props
  const [searchQuery, setSearchQuery] = useState("")
  const [selectedTool, setSelectedTool] = useState("All Tools")

  const allFiles: FileItem[] = files?.data ?? []

  const filtered = allFiles.filter((file) => {
    const matchesSearch = file.name.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesTool = selectedTool === "All Tools" || getToolLabel(file.tool) === selectedTool
    return matchesSearch && matchesTool
  })

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
        </div>

        {/* Files Table */}
        {filtered.length > 0 ? (
          <DocumateCard className="mt-6 overflow-hidden p-0">
            {/* Table Header */}
            <div className="grid grid-cols-[1fr_110px_90px_90px_110px_110px_48px] gap-4 bg-zinc-800/50 px-6 py-3 text-xs font-medium text-zinc-500">
              <span>File</span>
              <span>Tool</span>
              <span>Original</span>
              <span>Result</span>
              <span>Date</span>
              <span>Expires</span>
              <span></span>
            </div>

            {/* Table Rows */}
            {filtered.map((file) => {
              const saved = file.inputSize && file.outputSize
                ? Math.round((1 - file.outputSize / file.inputSize) * 100)
                : null

              return (
                <div
                  key={file.id}
                  className="grid grid-cols-[1fr_110px_90px_90px_110px_110px_48px] gap-4 border-t border-zinc-800 px-6 py-4 transition-colors hover:bg-zinc-800/30"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <FileText className="h-3.5 w-3.5 flex-shrink-0 text-red-400" />
                    <span className="truncate text-sm text-white">{file.name}</span>
                    {file.status === "failed" && (
                      <span className="flex-shrink-0 rounded-full bg-red-500/20 px-2 py-0.5 text-xs text-red-400">Failed</span>
                    )}
                    {file.status === "processing" || file.status === "pending" ? (
                      <span className="flex-shrink-0 rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs text-yellow-400">Processing</span>
                    ) : null}
                  </div>
                  <span className={`inline-flex h-fit w-fit items-center rounded-full px-2 py-0.5 text-xs ${getToolColor(file.tool)}`}>
                    {getToolLabel(file.tool)}
                  </span>
                  <span className="font-mono text-xs text-zinc-500">{formatSize(file.inputSize)}</span>
                  <div className="flex items-center gap-1.5">
                    <span className="font-mono text-xs text-zinc-500">{formatSize(file.outputSize)}</span>
                    {saved !== null && saved > 0 && (
                      <DocumateBadge variant="success">{saved}%</DocumateBadge>
                    )}
                  </div>
                  <span className="text-xs text-zinc-500">{file.date}</span>
                  <span className={`text-xs ${file.isExpired ? "text-red-400" : "text-zinc-400"}`}>
                    {file.expires}
                  </span>
                  <div className="flex items-center justify-end">
                    {file.canDownload && (
                      <a
                        href={route("tools.download", { uuid: file.uuid })}
                        className="rounded-lg p-1 text-zinc-500 transition-colors hover:bg-zinc-700 hover:text-white"
                        title="Download"
                      >
                        <Download className="h-4 w-4" />
                      </a>
                    )}
                  </div>
                </div>
              )
            })}
          </DocumateCard>
        ) : (
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
        {files && files.last_page > 1 && (
          <DocumateCard padding="sm" className="mt-6">
            <div className="flex items-center justify-between">
              <span className="text-sm text-zinc-500">
                Showing {((files.current_page - 1) * files.per_page) + 1}–{Math.min(files.current_page * files.per_page, files.total)} of {files.total} files
              </span>
              <div className="flex items-center gap-2">
                <a
                  href={files.prev_page_url ?? "#"}
                  className={`rounded-lg border border-zinc-700 p-1.5 text-zinc-500 transition-colors hover:border-zinc-500 hover:text-white ${!files.prev_page_url ? "pointer-events-none opacity-40" : ""}`}
                >
                  <ChevronLeft className="h-4 w-4" />
                </a>
                <span className="rounded-lg bg-zinc-700 px-3 py-1 text-sm text-white">{files.current_page}</span>
                <a
                  href={files.next_page_url ?? "#"}
                  className={`rounded-lg border border-zinc-700 p-1.5 text-zinc-500 transition-colors hover:border-zinc-500 hover:text-white ${!files.next_page_url ? "pointer-events-none opacity-40" : ""}`}
                >
                  <ChevronRight className="h-4 w-4" />
                </a>
              </div>
            </div>
          </DocumateCard>
        )}
      </div>
    </AppLayout>
  )
}
