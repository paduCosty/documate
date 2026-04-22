"use client"

import { useCallback, useRef, useState } from "react"
import { router } from "@inertiajs/react"
import {
  FileText, Upload, X, Zap, AlertCircle, ChevronDown, ChevronUp,
} from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateButton } from "@/components/documate/documate-button"
import { TemplateSelector, type Template } from "@/components/documate/extraction/TemplateSelector"
import { FormatSelector, type OutputFormat } from "@/components/documate/extraction/FormatSelector"
import { ProviderSelector, type AiProvider } from "@/components/documate/extraction/ProviderSelector"
import { cn } from "@/lib/utils"

interface Props {
  templates: Template[]
  providers: AiProvider[]
  formats: OutputFormat[]
  defaultFormat: string
  errors?: Record<string, string>
}

export default function ExtractPdfPage({
  templates,
  providers,
  formats,
  defaultFormat,
  errors = {},
}: Props) {
  const [file, setFile]             = useState<File | null>(null)
  const [isDragging, setIsDragging] = useState(false)
  const [template, setTemplate]     = useState(templates[0]?.slug ?? "")
  const [format, setFormat]         = useState(defaultFormat)
  const [provider, setProvider]     = useState<string | null>(null)
  const [showAdvanced, setShowAdvanced] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  const handleFile = (f: File) => {
    if (f.type !== "application/pdf" && !f.name.endsWith(".pdf")) return
    setFile(f)
  }

  const onDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    setIsDragging(false)
    const dropped = e.dataTransfer.files[0]
    if (dropped) handleFile(dropped)
  }, [])

  const onInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const picked = e.target.files?.[0]
    if (picked) handleFile(picked)
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!file || !template || isSubmitting) return

    setIsSubmitting(true)

    const formData = new FormData()
    formData.append("file", file)
    formData.append("template", template)
    formData.append("format", format)
    if (provider) formData.append("provider", provider)

    router.post(route("tools.extract-pdf.process"), formData, {
      forceFormData: true,
      onError: () => setIsSubmitting(false),
    })
  }

  const canSubmit = file !== null && template !== ""

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-3xl px-6 py-16">
        {/* Header */}
        <div className="mb-10 text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-800 shadow-lg">
            <Zap className="h-7 w-7 text-white" />
          </div>
          <h1 className="text-3xl font-bold text-white">Extract PDF Data</h1>
          <p className="mt-3 text-zinc-400">
            Upload a PDF and let AI extract structured data — invoices, tables, or any document.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Global error */}
          {errors.file && (
            <div className="flex items-start gap-3 rounded-xl border border-red-900 bg-red-950/40 p-4">
              <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-400" />
              <p className="text-sm text-red-300">{errors.file}</p>
            </div>
          )}

          {/* Dropzone */}
          <DocumateCard padding="none">
            <div
              onDragOver={(e) => { e.preventDefault(); setIsDragging(true) }}
              onDragLeave={() => setIsDragging(false)}
              onDrop={onDrop}
              onClick={() => inputRef.current?.click()}
              className={cn(
                "group relative flex cursor-pointer flex-col items-center justify-center rounded-2xl p-10 transition-all duration-150",
                isDragging
                  ? "border-2 border-dashed border-white bg-white/5"
                  : "border-2 border-dashed border-zinc-800 hover:border-zinc-600 hover:bg-zinc-800/30",
              )}
            >
              <input
                ref={inputRef}
                type="file"
                accept=".pdf,application/pdf"
                className="sr-only"
                onChange={onInputChange}
              />

              {file ? (
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-700">
                    <FileText className="h-5 w-5 text-zinc-300" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{file.name}</p>
                    <p className="text-xs text-zinc-500">
                      {(file.size / 1024 / 1024).toFixed(2)} MB
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={(e) => { e.stopPropagation(); setFile(null) }}
                    className="ml-4 rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-700 hover:text-white"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              ) : (
                <>
                  <Upload className="mb-3 h-8 w-8 text-zinc-600 transition-colors group-hover:text-zinc-400" />
                  <p className="text-sm font-medium text-zinc-300">
                    Drop your PDF here, or{" "}
                    <span className="text-white underline underline-offset-2">browse</span>
                  </p>
                  <p className="mt-1 text-xs text-zinc-600">PDF only — up to 20 MB</p>
                </>
              )}
            </div>
          </DocumateCard>

          {/* Template selector */}
          <div className="space-y-3">
            <label className="block text-sm font-medium text-zinc-200">
              Extraction Template
            </label>
            {errors.template && (
              <p className="text-xs text-red-400">{errors.template}</p>
            )}
            <TemplateSelector
              templates={templates}
              value={template}
              onChange={setTemplate}
            />
          </div>

          {/* Output format */}
          <div className="space-y-3">
            <label className="block text-sm font-medium text-zinc-200">
              Output Format
            </label>
            <FormatSelector formats={formats} value={format} onChange={setFormat} />
          </div>

          {/* Advanced — provider selector (only if multiple enabled) */}
          {providers.filter((p) => p.enabled).length > 1 && (
            <div>
              <button
                type="button"
                onClick={() => setShowAdvanced((v) => !v)}
                className="flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-300"
              >
                {showAdvanced ? (
                  <ChevronUp className="h-3.5 w-3.5" />
                ) : (
                  <ChevronDown className="h-3.5 w-3.5" />
                )}
                Advanced options
              </button>

              {showAdvanced && (
                <div className="mt-4">
                  <ProviderSelector
                    providers={providers}
                    value={provider}
                    onChange={setProvider}
                  />
                </div>
              )}
            </div>
          )}

          {/* Submit */}
          <DocumateButton
            type="submit"
            size="lg"
            className="w-full"
            disabled={!canSubmit}
            loading={isSubmitting}
          >
            <Zap className="h-4 w-4" />
            {isSubmitting ? "Extracting…" : "Extract Data"}
          </DocumateButton>

          <p className="text-center text-xs text-zinc-600">
            Files are automatically deleted after 24 hours.
          </p>
        </form>
      </main>

      <Footer />
    </div>
  )
}
