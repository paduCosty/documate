"use client"

import { useState, useCallback } from "react"
import { Link } from '@inertiajs/react';

import { Upload, X, Zap, GripVertical, FileText, CheckCircle2, Download, Lock } from "lucide-react"
import { Navbar } from "./navbar"
import { Footer } from "./footer"
import { PageHeader } from "./page-header"
import { DocumateButton } from "./documate-button"
import { DocumateCard } from "./documate-card"
import { cn } from "@/lib/utils"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"

interface FAQ {
  question: string
  answer: string
}

interface Step {
  title: string
  description: string
}

interface ToolLayoutProps {
  toolName: string
  toolDescription: string
  acceptedFormats: string[]
  maxFiles: number
  maxSizeMB: number
  faqs: FAQ[]
  steps: Step[]
  actionButtonText: string
  outputFileName?: string
}

interface UploadedFile {
  id: string
  name: string
  size: number
}

type ToolState = "idle" | "processing" | "success"

export function ToolLayout({
  toolName,
  toolDescription,
  acceptedFormats,
  maxFiles,
  maxSizeMB,
  faqs,
  steps,
  actionButtonText,
  outputFileName = "output.pdf",
}: ToolLayoutProps) {
  const [files, setFiles] = useState<UploadedFile[]>([])
  const [isDragging, setIsDragging] = useState(false)
  const [showBanner, setShowBanner] = useState(true)
  const [toolState, setToolState] = useState<ToolState>("idle")
  const [progress, setProgress] = useState(0)

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    setIsDragging(true)
  }, [])

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    setIsDragging(false)
  }, [])

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    setIsDragging(false)
    const droppedFiles = Array.from(e.dataTransfer.files).slice(0, maxFiles)
    const newFiles: UploadedFile[] = droppedFiles.map((file) => ({
      id: Math.random().toString(36).substring(7),
      name: file.name,
      size: file.size,
    }))
    setFiles((prev) => [...prev, ...newFiles].slice(0, maxFiles))
  }, [maxFiles])

  const handleFileSelect = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const selectedFiles = Array.from(e.target.files).slice(0, maxFiles)
      const newFiles: UploadedFile[] = selectedFiles.map((file) => ({
        id: Math.random().toString(36).substring(7),
        name: file.name,
        size: file.size,
      }))
      setFiles((prev) => [...prev, ...newFiles].slice(0, maxFiles))
    }
  }, [maxFiles])

  const removeFile = useCallback((id: string) => {
    setFiles((prev) => prev.filter((f) => f.id !== id))
  }, [])

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }

  const totalSize = files.reduce((acc, f) => acc + f.size, 0)

  const handleProcess = useCallback(() => {
    setToolState("processing")
    setProgress(0)
    const interval = setInterval(() => {
      setProgress((prev) => {
        if (prev >= 100) {
          clearInterval(interval)
          setToolState("success")
          return 100
        }
        return prev + 10
      })
    }, 200)
  }, [])

  const handleReset = useCallback(() => {
    setFiles([])
    setToolState("idle")
    setProgress(0)
  }, [])

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-2xl px-6 py-16">
        <PageHeader
          breadcrumbs={[
            { label: "Home", href: "/" },
            { label: "Tools", href: "/tools" },
            { label: toolName },
          ]}
          title={toolName}
          subtitle={toolDescription}
        />

        {/* Free Tier Banner */}
        {showBanner && toolState === "idle" && (
          <div className="mt-6 flex animate-in fade-in items-center justify-between rounded-xl border border-zinc-700 bg-zinc-900 p-3">
            <div className="flex items-center gap-2">
              <Zap className="h-3.5 w-3.5 text-amber-400" />
              <span className="text-sm text-zinc-400">
                Using 1 of 3 free daily operations.{" "}
                <Link href="/register" className="text-white underline underline-offset-2 hover:no-underline">
                  Create a free account for 10/day &rarr;
                </Link>
              </span>
            </div>
            <button
              onClick={() => setShowBanner(false)}
              className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
        )}

        {/* Idle State */}
        {toolState === "idle" && (
          <>
            {/* Dropzone */}
            <label
              onDragOver={handleDragOver}
              onDragLeave={handleDragLeave}
              onDrop={handleDrop}
              className={cn(
                "mt-6 flex min-h-[220px] cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-zinc-900 transition-all duration-150",
                isDragging ? "border-white bg-zinc-800" : "border-zinc-700"
              )}
            >
              <input
                type="file"
                multiple
                accept={acceptedFormats.join(",")}
                onChange={handleFileSelect}
                className="hidden"
              />
              <Upload className={cn("h-10 w-10", isDragging ? "text-white" : "text-zinc-600")} />
              <p className={cn("mt-4 font-medium", isDragging ? "text-white" : "text-white")}>
                Drop your PDF files here
              </p>
              <p className={cn("mt-1 text-sm", isDragging ? "text-zinc-300" : "text-zinc-500")}>
                or click to browse
              </p>
              <p className="mt-3 text-xs text-zinc-600">
                Up to {maxFiles} files &middot; Max {maxSizeMB}MB each
              </p>
            </label>

            {/* File List */}
            {files.length > 0 && (
              <div className="mt-3 space-y-2">
                {files.map((file) => (
                  <div
                    key={file.id}
                    className="flex animate-in slide-in-from-bottom-2 items-center gap-3 rounded-xl bg-zinc-800 p-3 duration-200"
                  >
                    <GripVertical className="h-4 w-4 cursor-grab text-zinc-600" />
                    <FileText className="h-4 w-4 text-red-400" />
                    <span className="flex-1 truncate text-sm font-medium text-white">{file.name}</span>
                    <span className="font-mono text-xs text-zinc-500">{formatFileSize(file.size)}</span>
                    <button
                      onClick={() => removeFile(file.id)}
                      className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white"
                    >
                      <X className="h-4 w-4" />
                    </button>
                  </div>
                ))}
                <p className="mt-2 text-right text-xs text-zinc-500">
                  Total: {formatFileSize(totalSize)}
                </p>
              </div>
            )}

            {/* Action Bar */}
            <div className="mt-6">
              <DocumateButton
                className="w-full py-3 text-base"
                disabled={files.length === 0}
                onClick={handleProcess}
              >
                {actionButtonText}
              </DocumateButton>
              <p className="mt-2 text-center text-xs text-zinc-600">
                Estimated time: ~3 seconds
              </p>
            </div>
          </>
        )}

        {/* Processing State */}
        {toolState === "processing" && (
          <DocumateCard className="mt-6">
            <p className="text-sm text-zinc-400">Processing your files...</p>
            <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800">
              <div
                className="h-full rounded-full bg-white transition-all duration-200"
                style={{ width: `${progress}%` }}
              />
            </div>
            <div className="mt-2 flex items-center justify-between">
              <span className="text-xs text-zinc-600">This usually takes a few seconds</span>
              <span className="text-xs text-zinc-600">{progress}%</span>
            </div>
          </DocumateCard>
        )}

        {/* Success State */}
        {toolState === "success" && (
          <DocumateCard className="mt-6 animate-in fade-in duration-300">
            <div className="mx-auto w-fit rounded-full bg-[rgba(34,197,94,0.10)] p-3">
              <CheckCircle2 className="h-6 w-6 text-[#22c55e]" />
            </div>
            <p className="mt-4 text-center font-semibold text-white">{outputFileName}</p>
            <p className="mt-1 text-center text-sm text-zinc-500">
              2.4 MB &rarr; 1.1 MB{" "}
              <span className="ml-1 inline-flex items-center rounded-full bg-[rgba(34,197,94,0.10)] px-2 py-0.5 text-xs text-[#22c55e]">
                54% smaller
              </span>
            </p>
            <DocumateButton className="mt-6 w-full">
              <Download className="h-4 w-4" />
              Download {outputFileName}
            </DocumateButton>
            <DocumateButton variant="ghost" className="mt-2 w-full" onClick={handleReset}>
              Process more files
            </DocumateButton>
            <div className="mt-6 border-t border-zinc-800 pt-6">
              <div className="flex items-center justify-center gap-1 text-xs text-zinc-600">
                <Lock className="h-3 w-3 text-zinc-700" />
                Your file will be automatically deleted in 24 hours
              </div>
            </div>
          </DocumateCard>
        )}

        {/* SEO Content Section */}
        <section className="mt-24 border-t border-zinc-800 pt-12">
          <h2 className="mb-8 text-xl font-semibold text-white">How to {toolName.toLowerCase()} online</h2>
          <div className="space-y-6">
            {steps.map((step, index) => (
              <div key={index} className="flex items-start gap-4">
                <div className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-zinc-800 font-mono text-xs text-zinc-400">
                  {index + 1}
                </div>
                <div>
                  <h3 className="font-medium text-white">{step.title}</h3>
                  <p className="mt-1 text-sm text-zinc-500">{step.description}</p>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* FAQ Section */}
        <section className="mt-16">
          <h3 className="mb-6 text-lg font-semibold text-white">Frequently asked questions</h3>
          <Accordion type="single" collapsible className="space-y-2">
            {faqs.map((faq, index) => (
              <AccordionItem
                key={index}
                value={`faq-${index}`}
                className="rounded-xl border border-zinc-800 bg-zinc-900 px-5"
              >
                <AccordionTrigger className="py-4 text-sm font-medium text-white hover:no-underline">
                  {faq.question}
                </AccordionTrigger>
                <AccordionContent className="pb-4 text-sm leading-6 text-zinc-400">
                  {faq.answer}
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        </section>
      </main>

      <Footer />
    </div>
  )
}
