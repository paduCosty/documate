"use client"

import { useState } from "react"
import { Link } from "@inertiajs/react"
import { SEOHead } from "@/components/documate/seo-head"
import { Search, GitMerge, Minimize2, FileText, Table, Presentation, Scissors, Image } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { PageHeader } from "@/components/documate/page-header"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateButton } from "@/components/documate/documate-button"

const tools = [
  {
    name: "Merge PDF",
    description: "Combine multiple PDFs into one file. Drag to reorder pages before merging.",
    icon: GitMerge,
    href: "/tools/merge-pdf",
    formats: ["PDF"],
    color: "text-blue-400",
  },
  {
    name: "Compress PDF",
    description: "Reduce PDF file size without losing quality. See before and after file sizes.",
    icon: Minimize2,
    href: "/tools/compress-pdf",
    formats: ["PDF"],
    color: "text-green-400",
  },
  {
    name: "Word to PDF",
    description: "Convert Microsoft Word documents (.doc, .docx) to PDF instantly.",
    icon: FileText,
    href: "/tools/word-to-pdf",
    formats: [".doc", ".docx"],
    color: "text-blue-500",
  },
  {
    name: "Excel to PDF",
    description: "Turn Excel spreadsheets into perfectly formatted PDF documents.",
    icon: Table,
    href: "/tools/excel-to-pdf",
    formats: [".xls", ".xlsx"],
    color: "text-emerald-500",
  },
  {
    name: "PPT to PDF",
    description: "Convert PowerPoint presentations to PDF with all slides intact.",
    icon: Presentation,
    href: "/tools/ppt-to-pdf",
    formats: [".ppt", ".pptx"],
    color: "text-orange-400",
  },
  {
    name: "Split PDF",
    description: "Extract specific pages or split into multiple files. Visual page picker included.",
    icon: Scissors,
    href: "/tools/split-pdf",
    formats: ["PDF"],
    color: "text-purple-400",
  },
  {
    name: "PDF to JPG",
    description: "Convert each PDF page into a high-quality JPG image.",
    icon: Image,
    href: "/tools/pdf-to-jpg",
    formats: ["PDF"],
    color: "text-pink-400",
  },
]

export default function ToolsPage() {
  const [searchQuery, setSearchQuery] = useState("")

  const filteredTools = tools.filter(
    (tool) =>
      tool.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      tool.description.toLowerCase().includes(searchQuery.toLowerCase())
  )

  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="All PDF Tools — Documate"
        description="Browse all free online PDF tools: merge, compress, split PDFs, convert Word, Excel, PowerPoint to PDF, and PDF to JPG."
        canonical="/tools"
      />
      <Navbar />

      <main className="mx-auto max-w-6xl px-6 py-16">
        <PageHeader
          title="All PDF Tools"
          subtitle="Free online tools for every PDF task."
        />

        {/* Search */}
        <div className="relative mt-8">
          <Search className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
          <input
            type="text"
            placeholder="Search tools..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full rounded-xl border border-zinc-800 bg-zinc-900 py-3 pl-12 pr-4 text-white placeholder:text-zinc-500 focus:border-zinc-600 focus:outline-none"
          />
        </div>

        {/* Tools Grid */}
        <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filteredTools.map((tool) => (
            <DocumateCard key={tool.name} hover className="flex flex-col">
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-zinc-800 p-3">
                  <tool.icon className={`h-5 w-5 ${tool.color}`} />
                </div>
                <span className="text-lg font-semibold text-white">{tool.name}</span>
              </div>
              <p className="mt-3 flex-1 text-sm leading-6 text-zinc-500">{tool.description}</p>
              <div className="mt-4 flex flex-wrap gap-2">
                {tool.formats.map((format) => (
                  <span
                    key={format}
                    className="rounded-full bg-zinc-800 px-2 py-0.5 text-xs text-zinc-400"
                  >
                    {format}
                  </span>
                ))}
              </div>
              <Link href={tool.href} className="mt-4">
                <DocumateButton size="sm" className="w-full">
                  Use tool &rarr;
                </DocumateButton>
              </Link>
            </DocumateCard>
          ))}
        </div>

        {filteredTools.length === 0 && (
          <div className="mt-16 text-center">
            <p className="text-zinc-500">No tools found matching &ldquo;{searchQuery}&rdquo;</p>
          </div>
        )}
      </main>

      <Footer />
    </div>
  )
}
