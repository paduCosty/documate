"use client";

import { useEffect, useRef, useState } from "react";
import { Link } from "@inertiajs/react";
import { CheckCircle2, Loader2, AlertCircle, Download, ArrowLeft } from "lucide-react";
import { Navbar } from "@/components/documate/navbar";
import { Footer } from "@/components/documate/footer";
import { DocumateCard } from "@/components/documate/documate-card";
import { DocumateButton } from "@/components/documate/documate-button";

interface Props {
    fileUuid: string;
    initialStatus: any;
}

const operationLabels: Record<string, { success: string; subtitle: (f: any) => string; download: string; again: string; processingMsg: string; }> = {
    merge_pdf:    { success: "Merge completed successfully!",       subtitle: (f) => `${f.original_filenames?.length ?? 0} files merged into one`, download: "Download Merged PDF",    again: "Merge another set",      processingMsg: "Merging your PDFs..."       },
    split_pdf:    { success: "Split completed successfully!",       subtitle: (f) => { const c = f.metadata?.split_count; return c ? `PDF split into ${c} file${c>1?"s":""}` : "Your PDF has been split"; }, download: "Download ZIP",             again: "Split another PDF",      processingMsg: "Splitting your PDF..."      },
    compress_pdf: { success: "Compression completed successfully!", subtitle: (f) => { const s = f.metadata?.saved_percent; return s ? `File size reduced by ${s}%` : "Your PDF has been compressed"; },  download: "Download Compressed PDF", again: "Compress another file",  processingMsg: "Compressing your PDF..."    },
    'word-to-pdf': { success: "Conversion completed successfully!", subtitle: (f) => { const c = f.metadata?.converted_count; return c && c > 1 ? `${c} files converted to PDF` : "Your Word document has been converted"; }, download: "Download PDF", again: "Convert another file", processingMsg: "Converting to PDF..." },
};
const defaultLabel = { success: "Processing completed!", subtitle: () => "Your file is ready", download: "Download File", again: "Process another file", processingMsg: "Processing your file..." };

export default function ToolStatusPage({ fileUuid, initialStatus }: Props) {
    const [fileStatus, setFileStatus]   = useState(initialStatus);
    const [progress, setProgress]       = useState(0);
    const [showSuccess, setShowSuccess] = useState(false);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    // Polling
    useEffect(() => {
        const poll = setInterval(async () => {
            if (fileStatus.status === "pending" || fileStatus.status === "processing") {
                try { const r = await fetch(`/status/${fileUuid}/poll`); if (r.ok) setFileStatus(await r.json()); } catch {}
            }
        }, 2000);
        return () => clearInterval(poll);
    }, [fileStatus.status]);

    // Progress animation – driven entirely by JS, no CSS tricks
    useEffect(() => {
        if (intervalRef.current) clearInterval(intervalRef.current);
        if (fileStatus.status === "failed") return;

        const cfg: Record<string, { target: number; speed: number; tick: number }> = {
            pending:    { target: 20,  speed: 0.35, tick: 80  },
            processing: { target: 75,  speed: 0.25, tick: 80  },
            completed:  { target: 100, speed: 3.5,  tick: 35  },
        };
        const { target, speed, tick } = cfg[fileStatus.status] ?? { target: 100, speed: 4, tick: 40 };

        intervalRef.current = setInterval(() => {
            setProgress(p => {
                const next = +(p + speed).toFixed(2);
                if (next >= target) { clearInterval(intervalRef.current!); return target; }
                return next;
            });
        }, tick);

        return () => { if (intervalRef.current) clearInterval(intervalRef.current); };
    }, [fileStatus.status]);

    // Only reveal success UI after bar physically reaches 100%
    useEffect(() => {
        if (fileStatus.status === "completed" && progress >= 100) {
            const t = setTimeout(() => setShowSuccess(true), 250);
            return () => clearTimeout(t);
        }
    }, [fileStatus.status, progress]);

    const isFailed   = fileStatus.status === "failed";
    const label      = operationLabels[fileStatus.operation_type] ?? defaultLabel;
    const pct        = Math.min(Math.round(progress), 100);
    const statusText = fileStatus.status === "pending" ? "Queued — waiting for worker..."
                     : fileStatus.status === "processing" ? "Processing on server..."
                     : "Finalising...";

    return (
        <div className="min-h-screen bg-zinc-950">
            <Navbar />
            <main className="mx-auto max-w-2xl px-6 py-16">
                <Link href="/tools" className="inline-flex items-center gap-2 text-zinc-400 hover:text-white mb-8">
                    <ArrowLeft className="h-4 w-4" /> Back to Tools
                </Link>

                <DocumateCard className="p-10 text-center">

                    {/* Processing / bar – shown until bar reaches 100% */}
                    {!showSuccess && !isFailed && (
                        <>
                            <Loader2 className="mx-auto h-12 w-12 animate-spin text-white" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">{label.processingMsg}</h2>
                            <p className="mt-3 text-zinc-400">This usually takes just a few seconds.</p>
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

                    {/* Success – shown only after bar hits 100% */}
                    {showSuccess && (
                        <>
                            <div className="mx-auto w-fit rounded-full bg-emerald-950 p-4">
                                <CheckCircle2 className="h-12 w-12 text-emerald-400" />
                            </div>
                            <h2 className="mt-6 text-2xl font-semibold text-white">{label.success}</h2>
                            <p className="mt-2 text-zinc-400">{label.subtitle(fileStatus)}</p>
                            <DocumateButton className="mt-10 w-full" as="a" href={`/tools/download/${fileStatus.uuid}`}>
                                <Download className="mr-2 h-4 w-4" />{label.download}
                            </DocumateButton>
                            <DocumateButton variant="ghost" className="mt-3 w-full" onClick={() => window.history.back()}>
                                {label.again}
                            </DocumateButton>
                        </>
                    )}

                    {/* Failed */}
                    {isFailed && (
                        <>
                            <AlertCircle className="mx-auto h-12 w-12 text-red-400" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">Processing failed</h2>
                            <p className="mt-4 text-zinc-400">Something went wrong. Please try again.</p>
                            <DocumateButton variant="outline" className="mt-8" onClick={() => window.history.back()}>
                                Try Again
                            </DocumateButton>
                        </>
                    )}

                </DocumateCard>

                <p className="mt-8 text-center text-xs text-zinc-500">
                    Your file will be automatically deleted after 24 hours for privacy reasons.
                </p>
            </main>
            <Footer />
        </div>
    );
}
