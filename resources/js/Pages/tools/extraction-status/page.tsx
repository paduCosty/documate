"use client"

import { useEffect, useRef, useState } from "react"
import { Link } from "@inertiajs/react"
import {
  CheckCircle2, Loader2, AlertCircle, Download, ArrowLeft,
  FileText, Clock, Cpu, Hash,
} from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateButton } from "@/components/documate/documate-button"
import { ExtractionDataPreview } from "@/components/documate/extraction/ExtractionDataPreview"

interface ExtractionJobStatus {
  uuid: string
  status: "pending" | "processing" | "completed" | "failed"
  original_filename: string
  output_format: string
  page_count: number | null
  tokens_used: number | null
  processing_time_ms: number | null
  error_message: string | null
  extracted_data: Record<string, unknown> | null
  is_expired: boolean
  can_download: boolean
  expires_at: string | null
  processed_at: string | null
}

interface Props {
  jobUuid: string
  initialStatus: ExtractionJobStatus
}

export default function ExtractionStatusPage({ jobUuid, initialStatus }: Props) {
  const [job, setJob]                 = useState(initialStatus)
  const [progress, setProgress]       = useState(0)
  const [showSuccess, setShowSuccess] = useState(false)
  const progressRef = useRef<ReturnType<typeof setInterval> | null>(null)

  // Polling every 2s while pending/processing
  useEffect(() => {
    if (job.status !== "pending" && job.status !== "processing") return

    const poll = setInterval(async () => {
      try {
        const res = await fetch(route("extraction.poll", jobUuid))
        if (res.ok) setJob(await res.json())
      } catch {}
    }, 2000)

    return () => clearInterval(poll)
  }, [job.status, jobUuid])

  // Progress bar animation
  useEffect(() => {
    if (progressRef.current) clearInterval(progressRef.current)
    if (job.status === "failed") return

    const configs: Record<string, { target: number; speed: number; tick: number }> = {
      pending:    { target: 20,  speed: 0.3, tick: 80 },
      processing: { target: 80,  speed: 0.2, tick: 80 },
      completed:  { target: 100, speed: 4,   tick: 35 },
    }

    const { target, speed, tick } = configs[job.status] ?? { target: 100, speed: 4, tick: 40 }

    progressRef.current = setInterval(() => {
      setProgress((p) => {
        const next = +(p + speed).toFixed(2)
        if (next >= target) {
          clearInterval(progressRef.current!)
          return target
        }
        return next
      })
    }, tick)

    return () => { if (progressRef.current) clearInterval(progressRef.current) }
  }, [job.status])

  // Reveal success UI after bar reaches 100%
  useEffect(() => {
    if (job.status === "completed" && progress >= 100) {
      const t = setTimeout(() => setShowSuccess(true), 250)
      return () => clearTimeout(t)
    }
  }, [job.status, progress])

  const pct = Math.min(Math.round(progress), 100)
  const isFailed = job.status === "failed"

  const statusText =
    job.status === "pending"
      ? "Queued — waiting for worker…"
      : job.status === "processing"
      ? "Extracting data with AI…"
      : "Finalising…"

  const formatExt = job.output_format?.toUpperCase() ?? "FILE"

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-2xl px-6 py-16">
        <Link
          href={route("tools.extract-pdf")}
          className="mb-8 inline-flex items-center gap-2 text-zinc-400 hover:text-white"
        >
          <ArrowLeft className="h-4 w-4" />
          Extract another PDF
        </Link>

        {/* Main card */}
        <DocumateCard className="p-10 text-center">

          {/* Processing */}
          {!showSuccess && !isFailed && (
            <>
              <Loader2 className="mx-auto h-12 w-12 animate-spin text-white" />
              <h2 className="mt-6 text-2xl font-semibold text-white">
                Extracting data…
              </h2>
              <p className="mt-2 text-zinc-400">
                AI is analysing{" "}
                <span className="font-medium text-zinc-200">
                  {job.original_filename}
                </span>
              </p>
              <div className="mt-8 space-y-2">
                <div className="h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                  <div
                    className="h-full rounded-full bg-white transition-[width] duration-100 ease-out"
                    style={{ width: `${pct}%` }}
                  />
                </div>
                <div className="flex items-center justify-between text-xs text-zinc-600">
                  <span>{statusText}</span>
                  <span className="font-mono tabular-nums">{pct}%</span>
                </div>
              </div>
            </>
          )}

          {/* Success */}
          {showSuccess && job.can_download && (
            <>
              <div className="mx-auto w-fit rounded-full bg-emerald-950 p-4">
                <CheckCircle2 className="h-12 w-12 text-emerald-400" />
              </div>
              <h2 className="mt-6 text-2xl font-semibold text-white">
                Extraction complete!
              </h2>
              <p className="mt-2 text-zinc-400">
                Data extracted from{" "}
                <span className="font-medium text-zinc-200">{job.original_filename}</span>
              </p>

              {/* Stats row */}
              <div className="mt-6 flex justify-center gap-6 text-xs text-zinc-500">
                {job.page_count != null && (
                  <StatBadge icon={<FileText className="h-3.5 w-3.5" />} label={`${job.page_count} page${job.page_count !== 1 ? "s" : ""}`} />
                )}
                {job.tokens_used != null && (
                  <StatBadge icon={<Hash className="h-3.5 w-3.5" />} label={`${job.tokens_used} tokens`} />
                )}
                {job.processing_time_ms != null && (
                  <StatBadge icon={<Clock className="h-3.5 w-3.5" />} label={`${(job.processing_time_ms / 1000).toFixed(1)}s`} />
                )}
              </div>

              <DocumateButton
                as="a"
                href={route("extraction.download", jobUuid)}
                className="mt-8 w-full"
                size="lg"
              >
                <Download className="h-4 w-4" />
                Download {formatExt}
              </DocumateButton>

              <DocumateButton
                variant="ghost"
                className="mt-3 w-full"
                as="a"
                href={route("tools.extract-pdf")}
              >
                Extract another PDF
              </DocumateButton>
            </>
          )}

          {/* Failed */}
          {isFailed && (
            <>
              <div className="mx-auto w-fit rounded-full bg-red-950 p-4">
                <AlertCircle className="h-12 w-12 text-red-400" />
              </div>
              <h2 className="mt-6 text-2xl font-semibold text-white">
                Extraction failed
              </h2>
              {job.error_message && (
                <p className="mt-3 rounded-lg bg-zinc-800 px-4 py-3 text-left text-xs text-zinc-400 font-mono">
                  {job.error_message}
                </p>
              )}
              <DocumateButton
                variant="outline"
                className="mt-8"
                as="a"
                href={route("tools.extract-pdf")}
              >
                Try again
              </DocumateButton>
            </>
          )}
        </DocumateCard>

        {/* Data preview — shown below the main card once completed */}
        {showSuccess && job.extracted_data && Object.keys(job.extracted_data).length > 0 && (
          <div className="mt-8">
            <h3 className="mb-3 text-sm font-medium text-zinc-400">
              Extracted Data Preview
            </h3>
            <ExtractionDataPreview data={job.extracted_data} />
          </div>
        )}

        <p className="mt-8 text-center text-xs text-zinc-600">
          Files are automatically deleted after 24 hours for privacy.
        </p>
      </main>

      <Footer />
    </div>
  )
}

function StatBadge({
  icon,
  label,
}: {
  icon: React.ReactNode
  label: string
}) {
  return (
    <div className="flex items-center gap-1.5">
      {icon}
      <span>{label}</span>
    </div>
  )
}
