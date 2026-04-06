"use client";

import { useEffect, useState } from "react";
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

const operationLabels: Record<string, { success: string; subtitle: (f: any) => string; download: string; again: string }> = {
    merge_pdf: {
        success: "Merge completed successfully!",
        subtitle: (f) => `${f.original_filenames?.length ?? 0} files have been merged`,
        download: "Download Merged PDF",
        again: "Merge another set",
    },
    compress_pdf: {
        success: "Compression completed successfully!",
        subtitle: (f) => {
            const saved = f.metadata?.saved_percent;
            return saved ? `File size reduced by ${saved}%` : "Your PDF has been compressed";
        },
        download: "Download Compressed PDF",
        again: "Compress another file",
    },
};

const defaultLabel = {
    success: "Processing completed!",
    subtitle: () => "Your file is ready",
    download: "Download File",
    again: "Process another file",
};

export default function ToolStatusPage({ fileUuid, initialStatus }: Props) {
    const [fileStatus, setFileStatus] = useState(initialStatus);

    const checkStatus = async () => {
        try {
            const response = await fetch(`/status/${fileUuid}/poll`);
            if (!response.ok) throw new Error("Failed to fetch");
            const data = await response.json();
            setFileStatus(data);
        } catch (error) {
            console.error("Polling error:", error);
        }
    };

    useEffect(() => {
        const interval = setInterval(() => {
            if (fileStatus.status === "pending" || fileStatus.status === "processing") {
                checkStatus();
            }
        }, 2000);
        return () => clearInterval(interval);
    }, [fileStatus.status]);

    const isProcessing = fileStatus.status === "pending" || fileStatus.status === "processing";
    const isCompleted = fileStatus.status === "completed";
    const isFailed = fileStatus.status === "failed";

    const label = operationLabels[fileStatus.operation_type] ?? defaultLabel;

    return (
        <div className="min-h-screen bg-zinc-950">
            <Navbar />

            <main className="mx-auto max-w-2xl px-6 py-16">
                <Link
                    href="/tools"
                    className="inline-flex items-center gap-2 text-zinc-400 hover:text-white mb-8"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Tools
                </Link>

                <DocumateCard className="p-10 text-center">
                    {isProcessing && (
                        <>
                            <Loader2 className="mx-auto h-12 w-12 animate-spin text-white" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">Processing your file...</h2>
                            <p className="mt-3 text-zinc-400">This usually takes just a few seconds.</p>
                            <div className="mt-8 h-1.5 w-full overflow-hidden rounded-full bg-zinc-800">
                                <div className="h-full w-3/4 animate-[loading_1.8s_infinite] rounded-full bg-white" />
                            </div>
                        </>
                    )}

                    {isCompleted && (
                        <>
                            <div className="mx-auto w-fit rounded-full bg-emerald-950 p-4">
                                <CheckCircle2 className="h-12 w-12 text-emerald-400" />
                            </div>
                            <h2 className="mt-6 text-2xl font-semibold text-white">{label.success}</h2>
                            <p className="mt-2 text-zinc-400">{label.subtitle(fileStatus)}</p>

                            <DocumateButton
                                className="mt-10 w-full"
                                as="a"
                                href={`/tools/download/${fileStatus.uuid}`}
                            >
                                <Download className="mr-2 h-4 w-4" />
                                {label.download}
                            </DocumateButton>

                            <DocumateButton
                                variant="ghost"
                                className="mt-3 w-full"
                                onClick={() => window.history.back()}
                            >
                                {label.again}
                            </DocumateButton>
                        </>
                    )}

                    {isFailed && (
                        <>
                            <AlertCircle className="mx-auto h-12 w-12 text-red-400" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">Processing failed</h2>
                            <p className="mt-4 text-zinc-400">
                                Something went wrong while processing your file. Please try again.
                            </p>
                            <DocumateButton
                                variant="outline"
                                className="mt-8"
                                onClick={() => window.history.back()}
                            >
                                Try Again
                            </DocumateButton>
                        </>
                    )}
                </DocumateCard>

                <div className="mt-8 text-center text-xs text-zinc-500">
                    Your file will be automatically deleted after 24 hours for privacy reasons.
                </div>
            </main>

            <Footer />
        </div>
    );
}
