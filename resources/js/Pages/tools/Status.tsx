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
    initialStatus: any;        // Statusul inițial trimis din controller
}

export default function ToolStatusPage({ fileUuid, initialStatus }: Props) {
    const [fileStatus, setFileStatus] = useState(initialStatus);
    const [isPolling, setIsPolling] = useState(true);

    // Polling function
    const checkStatus = async () => {
        try {
            const response = await fetch(`/tools/status/${fileUuid}/poll`);
            if (!response.ok) throw new Error("Failed to fetch");

            const data = await response.json();
            setFileStatus(data);

            // Stop polling when finished
            if (data.status === 'completed' || data.status === 'failed') {
                setIsPolling(false);
            }
        } catch (error) {
            console.error("Polling error:", error);
        }
    };

    useEffect(() => {
        // Start polling every 2 seconds while still processing
        const interval = setInterval(() => {
            if (fileStatus.status === 'pending' || fileStatus.status === 'processing') {
                checkStatus();
            } else {
                setIsPolling(false);
            }
        }, 2000);

        return () => clearInterval(interval);
    }, [fileStatus.status]);

    const isProcessing = fileStatus.status === 'pending' || fileStatus.status === 'processing';
    const isCompleted = fileStatus.status === 'completed';
    const isFailed = fileStatus.status === 'failed';

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
                    {/* Processing State */}
                    {isProcessing && (
                        <>
                            <Loader2 className="mx-auto h-12 w-12 animate-spin text-white" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">Processing your files...</h2>
                            <p className="mt-3 text-zinc-400">
                                Merging your PDFs. This usually takes just a few seconds.
                            </p>
                            <div className="mt-8 h-1.5 w-full overflow-hidden rounded-full bg-zinc-800">
                                <div className="h-full w-3/4 animate-[loading_1.8s_infinite] rounded-full bg-white" />
                            </div>
                        </>
                    )}

                    {/* Success State */}
                    {isCompleted && (
                        <>
                            <div className="mx-auto w-fit rounded-full bg-emerald-950 p-4">
                                <CheckCircle2 className="h-12 w-12 text-emerald-400" />
                            </div>
                            <h2 className="mt-6 text-2xl font-semibold text-white">
                                Merge completed successfully!
                            </h2>
                            <p className="mt-2 text-zinc-400">
                                {fileStatus.original_filenames.length} files have been merged
                            </p>

                            <DocumateButton
                                className="mt-10 w-full"
                                as="a"
                                href={`/tools/download/${fileStatus.uuid}`}
                            >
                                <Download className="mr-2 h-4 w-4" />
                                Download Merged PDF
                            </DocumateButton>

                            <DocumateButton
                                variant="ghost"
                                className="mt-3 w-full"
                                onClick={() => window.location.href = '/tools'}
                            >
                                Merge another set
                            </DocumateButton>
                        </>
                    )}

                    {/* Failed State */}
                    {isFailed && (
                        <>
                            <AlertCircle className="mx-auto h-12 w-12 text-red-400" />
                            <h2 className="mt-6 text-2xl font-semibold text-white">Processing failed</h2>
                            <p className="mt-4 text-zinc-400">
                                Something went wrong while merging your files. Please try again.
                            </p>
                            <DocumateButton
                                variant="outline"
                                className="mt-8"
                                onClick={() => window.location.href = '/tools'}
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