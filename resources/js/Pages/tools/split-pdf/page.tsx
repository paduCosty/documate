"use client";

import { useState, useCallback, useRef, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import { SEOHead } from "@/components/documate/seo-head";
import {
  Upload, X, Zap, GripVertical, FileText, Scissors,
  RotateCcw, Trash2, ChevronRight, HelpCircle, Move,
} from "lucide-react";
import { Navbar } from "@/components/documate/navbar";
import { Footer } from "@/components/documate/footer";
import { PageHeader } from "@/components/documate/page-header";
import { DocumateButton } from "@/components/documate/documate-button";
import { DocumateCard } from "@/components/documate/documate-card";
import { cn } from "@/lib/utils";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

// ─── Group colours ────────────────────────────────────────────────────────────
const GROUP_BORDER = ["border-blue-500","border-violet-500","border-emerald-500","border-amber-500","border-rose-500","border-cyan-500"];
const GROUP_BAR    = ["bg-blue-500","bg-violet-500","bg-emerald-500","bg-amber-500","bg-rose-500","bg-cyan-500"];
const GROUP_DOT    = ["bg-blue-500","bg-violet-500","bg-emerald-500","bg-amber-500","bg-rose-500","bg-cyan-500"];

// ─── Types ────────────────────────────────────────────────────────────────────
interface PageItem {
  id: string;
  pageNum: number;
  thumbnail: string;
  splitAfter: boolean;
  deleted: boolean;
}

type AppState = "idle" | "loading" | "editor" | "processing";

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatFileSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function getGroups(pageList: PageItem[]): number[][] {
  const active = pageList.filter((p) => !p.deleted);
  if (active.length === 0) return [];
  const groups: number[][] = [];
  let current: number[] = [];
  for (const p of active) {
    current.push(p.pageNum);
    if (p.splitAfter) { groups.push(current); current = []; }
  }
  if (current.length > 0) groups.push(current);
  return groups;
}

function getGroupMap(pageList: PageItem[]): Map<string, number> {
  const map = new Map<string, number>();
  const active = pageList.filter((p) => !p.deleted);
  let g = 0;
  for (const p of active) {
    map.set(p.id, g);
    if (p.splitAfter) g++;
  }
  return map;
}

// ─── Tutorial modal ───────────────────────────────────────────────────────────
function TutorialModal({ onClose }: { onClose: () => void }) {
  const steps = [
    {
      icon: <Move className="h-6 w-6 text-blue-400" />,
      color: "border-blue-500/30 bg-blue-950/30",
      title: "Drag to reorder",
      desc: "Grab any page thumbnail and drag it to a new position. The output PDF will follow your order, not the original.",
    },
    {
      icon: <Scissors className="h-6 w-6 text-violet-400" />,
      color: "border-violet-500/30 bg-violet-950/30",
      title: "Click scissors to split",
      desc: "The scissors icon between two pages marks a split point. Pages before the scissors form one PDF; pages after start a new one.",
    },
    {
      icon: <Trash2 className="h-6 w-6 text-red-400" />,
      color: "border-red-500/30 bg-red-950/30",
      title: "Trash to remove a page",
      desc: "Click the trash icon on any thumbnail to exclude that page. It turns ghosted so you can restore it any time before downloading.",
    },
    {
      icon: <RotateCcw className="h-6 w-6 text-emerald-400" />,
      color: "border-emerald-500/30 bg-emerald-950/30",
      title: "Restore deleted pages",
      desc: "Changed your mind? Click the green restore icon on any ghosted page to bring it back into the output.",
    },
  ];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" onClick={onClose}>
      <div
        className="relative w-full max-w-lg rounded-2xl border border-zinc-700 bg-zinc-900 p-6 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <button onClick={onClose} className="absolute right-4 top-4 rounded-lg p-1 text-zinc-500 hover:bg-zinc-800 hover:text-white transition-colors">
          <X className="h-4 w-4" />
        </button>

        <h2 className="text-lg font-semibold text-white mb-1">How to use the Visual Editor</h2>
        <p className="text-sm text-zinc-500 mb-5">
          Arrange your PDF pages visually, split them into groups, and each group becomes a separate PDF in the ZIP.
        </p>

        <div className="space-y-3">
          {steps.map((step, i) => (
            <div key={i} className={cn("flex items-start gap-3 rounded-xl border p-3", step.color)}>
              <div className="mt-0.5 shrink-0">{step.icon}</div>
              <div>
                <p className="font-medium text-white text-sm">{step.title}</p>
                <p className="text-xs text-zinc-400 mt-0.5 leading-relaxed">{step.desc}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Example illustration */}
        <div className="mt-5 rounded-xl border border-zinc-700 bg-zinc-800/50 p-3">
          <p className="text-xs text-zinc-500 mb-2 font-medium uppercase tracking-wider">Example</p>
          <div className="flex items-center gap-1 flex-wrap">
            {[
              { label: "1", color: "border-blue-500", bar: "bg-blue-500" },
              { label: "4", color: "border-blue-500", bar: "bg-blue-500" },
              { label: "2", color: "border-blue-500", bar: "bg-blue-500" },
            ].map((p, i) => (
              <div key={i} className="flex items-center">
                <div className={cn("w-8 rounded border-2 overflow-hidden text-center text-xs font-mono text-zinc-300", p.color)}>
                  <div className={cn("h-0.5 w-full", p.bar)} />
                  <div className="bg-zinc-700 h-10 flex items-center justify-center">{p.label}</div>
                </div>
                {i < 2 && <div className="mx-0.5 text-zinc-700 text-xs">·</div>}
              </div>
            ))}
            <div className="mx-1">
              <div className="flex flex-col items-center gap-0.5 text-white">
                <div className="w-px h-3 bg-white" /><Scissors className="h-3 w-3" /><div className="w-px h-3 bg-white" />
              </div>
            </div>
            {[
              { label: "5", color: "border-violet-500", bar: "bg-violet-500" },
              { label: "3", color: "border-violet-500", bar: "bg-violet-500" },
            ].map((p, i) => (
              <div key={i} className="flex items-center">
                <div className={cn("w-8 rounded border-2 overflow-hidden text-center text-xs font-mono text-zinc-300", p.color)}>
                  <div className={cn("h-0.5 w-full", p.bar)} />
                  <div className="bg-zinc-700 h-10 flex items-center justify-center">{p.label}</div>
                </div>
                {i < 1 && <div className="mx-0.5 text-zinc-700 text-xs">·</div>}
              </div>
            ))}
            <div className="ml-3 text-xs text-zinc-400">
              → <span className="text-blue-400">part_1.pdf</span> (pages 1,4,2)<br/>
              → <span className="text-violet-400">part_2.pdf</span> (pages 5,3)
            </div>
          </div>
        </div>

        <button
          onClick={onClose}
          className="mt-4 w-full rounded-xl bg-white py-2.5 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 transition-colors"
        >
          Got it, let me try
        </button>
      </div>
    </div>
  );
}

// ─── Static content ───────────────────────────────────────────────────────────
const faqs = [
  {
    question: "How does the visual editor work?",
    answer: "After uploading your PDF, each page appears as a thumbnail. Drag pages to reorder them, click the scissors icon between pages to mark a split point, and use the trash icon to remove pages from the output.",
  },
  {
    question: "What does the scissors button do?",
    answer: "Clicking scissors between two pages marks a group boundary. Pages in the same group are merged into one PDF. Pages after the scissors start a new PDF. Add as many split points as you like.",
  },
  {
    question: "Can I reorder pages freely?",
    answer: "Yes. Drag any page thumbnail to a new position. The output PDF will use the order you set, not the original order.",
  },
  {
    question: "Can I remove pages from the output?",
    answer: "Yes. Click the trash icon on a thumbnail to exclude it. Removed pages appear ghosted so you can restore them any time before submitting.",
  },
  {
    question: "What do I receive after splitting?",
    answer: "A ZIP file with one PDF per group, named part_01_pages_1-3.pdf, part_02_pages_5-5.pdf, etc. Files are stored for 24 hours.",
  },
];

const steps = [
  { title: "Upload your PDF", description: "Drop your PDF into the upload area or click to browse. Only one file at a time." },
  { title: "Arrange and group pages", description: "Drag thumbnails to reorder, click scissors to split into groups, or trash pages you don't need." },
  { title: "Download the ZIP", description: "Click Split & Download. Each group becomes a separate PDF inside a ZIP archive." },
];

// ─── Component ────────────────────────────────────────────────────────────────
export default function SplitPdfPage() {
  const { auth } = usePage().props as any
  const isLoggedIn = !!auth?.user

  const [appState, setAppState] = useState<AppState>("idle");
  const [isDraggingOver, setIsDraggingOver] = useState(false);
  const [showBanner, setShowBanner] = useState(true);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [loadingProgress, setLoadingProgress] = useState(0);
  const [pages, setPages] = useState<PageItem[]>([]);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [draggedId, setDraggedId] = useState<string | null>(null);
  const [dragOverId, setDragOverId] = useState<string | null>(null);
  const [showTutorial, setShowTutorial] = useState(false);
  const [showFirstRunBanner, setShowFirstRunBanner] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Show tutorial on first editor open (via localStorage)
  useEffect(() => {
    if (appState === "editor") {
      const seen = localStorage.getItem("split_pdf_tutorial_seen");
      if (!seen) {
        setShowFirstRunBanner(true);
      }
    }
  }, [appState]);

  const dismissFirstRun = () => {
    localStorage.setItem("split_pdf_tutorial_seen", "1");
    setShowFirstRunBanner(false);
  };

  const openTutorial = () => {
    dismissFirstRun();
    setShowTutorial(true);
  };

  // ── File selection ──────────────────────────────────────────────────────────
  const acceptFile = useCallback((file: File) => {
    if (file.type !== "application/pdf") { setErrorMessage("Please upload a PDF file."); return; }
    setSelectedFile(file);
    setErrorMessage(null);
  }, []);

  const handleDropZone = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDraggingOver(false);
    const file = e.dataTransfer.files[0];
    if (file) acceptFile(file);
  }, [acceptFile]);

  // ── Load thumbnails via PDF.js ──────────────────────────────────────────────
  const openEditor = useCallback(async () => {
    if (!selectedFile) return;
    setAppState("loading");
    setLoadingProgress(0);
    setErrorMessage(null);
    try {
      const pdfjsLib = await import("pdfjs-dist");
      pdfjsLib.GlobalWorkerOptions.workerSrc = new URL("pdfjs-dist/build/pdf.worker.min.mjs", import.meta.url).href;
      const buf = await selectedFile.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
      const total = pdf.numPages;
      const newPages: PageItem[] = [];
      for (let i = 1; i <= total; i++) {
        const pg = await pdf.getPage(i);
        const vp = pg.getViewport({ scale: 0.4 });
        const canvas = document.createElement("canvas");
        canvas.width = vp.width;
        canvas.height = vp.height;
        await pg.render({ canvasContext: canvas.getContext("2d")!, viewport: vp }).promise;
        newPages.push({ id: `p${i}`, pageNum: i, thumbnail: canvas.toDataURL("image/jpeg", 0.7), splitAfter: false, deleted: false });
        setLoadingProgress(Math.round((i / total) * 100));
      }
      setPages(newPages);
      setAppState("editor");
    } catch (err) {
      console.error(err);
      setErrorMessage("Failed to read the PDF. Please try a different file.");
      setAppState("idle");
    }
  }, [selectedFile]);

  // ── Page operations ─────────────────────────────────────────────────────────
  const toggleSplit  = (id: string) => setPages((prev) => prev.map((p) => (p.id === id ? { ...p, splitAfter: !p.splitAfter } : p)));
  const toggleDelete = (id: string) => setPages((prev) => prev.map((p) => (p.id === id ? { ...p, deleted: !p.deleted } : p)));

  const splitAll = () => setPages((prev) => {
    const active = prev.filter((p) => !p.deleted);
    const lastId = active[active.length - 1]?.id;
    return prev.map((p) => ({ ...p, splitAfter: p.deleted || p.id === lastId ? false : true }));
  });

  const mergeAll = () => setPages((prev) => prev.map((p) => ({ ...p, splitAfter: false })));

  // ── Drag-to-reorder ─────────────────────────────────────────────────────────
  const onDragStart = (e: React.DragEvent, id: string) => { setDraggedId(id); e.dataTransfer.effectAllowed = "move"; };
  const onDragOver  = (e: React.DragEvent, id: string) => { e.preventDefault(); setDragOverId(id); };

  const onDropPage = (e: React.DragEvent, targetId: string) => {
    e.preventDefault();
    if (!draggedId || draggedId === targetId) { setDraggedId(null); setDragOverId(null); return; }
    setPages((prev) => {
      const from = prev.findIndex((p) => p.id === draggedId);
      const to   = prev.findIndex((p) => p.id === targetId);
      if (from === -1 || to === -1) return prev;
      const next = [...prev];
      const [moved] = next.splice(from, 1);
      next.splice(to, 0, moved);
      return next;
    });
    setDraggedId(null);
    setDragOverId(null);
  };

  // ── Submit ──────────────────────────────────────────────────────────────────
  const handleSubmit = () => {
    if (!selectedFile) return;
    const groups = getGroups(pages);
    if (groups.length === 0) { setErrorMessage("No pages selected. Restore some pages before submitting."); return; }
    setAppState("processing");
    const fd = new FormData();
    fd.append("file", selectedFile);
    fd.append("groups", JSON.stringify(groups));
    router.post("/tools/split-pdf", fd, {
      forceFormData: true,
      onError: (errs) => { setErrorMessage(Object.values(errs).join(" ")); setAppState("editor"); },
    });
  };

  // ── Derived ─────────────────────────────────────────────────────────────────
  const groupMap   = getGroupMap(pages);
  const groups     = getGroups(pages);
  const activeCount = pages.filter((p) => !p.deleted).length;

  // ─────────────────────────────────────────────────────────────────────────────
  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Split PDF Online Free — Documate"
        description="Split and rearrange PDF pages visually. Remove, reorder, or group pages, then download each group as a separate PDF. Free, fast, no software needed."
        canonical="https://documate.nexkit.app/tools/split-pdf"
      />
      <Navbar />

      {showTutorial && <TutorialModal onClose={() => setShowTutorial(false)} />}

      {/* ── UPLOAD / LOADING / PROCESSING ── */}
      {appState !== "editor" && (
        <main className="mx-auto max-w-2xl px-6 py-16">
          <PageHeader
            breadcrumbs={[{ label: "Home", href: "/" }, { label: "Tools", href: "/tools" }, { label: "Split PDF" }]}
            title="Split PDF"
            subtitle="Upload a PDF, visually arrange its pages into groups, and download each group as a separate file."
          />

          {showBanner && appState === "idle" && (
            <div className="mt-6 flex animate-in fade-in items-center justify-between rounded-xl border border-zinc-700 bg-zinc-900 p-3">
              <div className="flex items-center gap-2">
                <Zap className="h-3.5 w-3.5 text-amber-400" />
                <span className="text-sm text-zinc-400">
                  {isLoggedIn ? (
                    <>
                      3 free operations per day.{" "}
                      <Link href="/pricing" className="text-white underline underline-offset-2 hover:no-underline">Upgrade to Pro for unlimited →</Link>
                    </>
                  ) : (
                    <>
                      3 free operations per day.{" "}
                      <Link href="/register" className="text-white underline underline-offset-2 hover:no-underline">Sign up free</Link>
                      {" · "}
                      <Link href="/pricing" className="text-white underline underline-offset-2 hover:no-underline">Go Pro for unlimited →</Link>
                    </>
                  )}
                </span>
              </div>
              <button onClick={() => setShowBanner(false)} className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white"><X className="h-4 w-4" /></button>
            </div>
          )}

          {errorMessage && (
            <div className="mt-4 rounded-xl bg-red-950 border border-red-900 p-4 text-red-400 text-sm">{errorMessage}</div>
          )}

          {appState === "idle" && (
            <>
              <label
                onDragOver={(e) => { e.preventDefault(); setIsDraggingOver(true); }}
                onDragLeave={() => setIsDraggingOver(false)}
                onDrop={handleDropZone}
                className={cn(
                  "mt-6 flex min-h-[220px] cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-zinc-900 transition-all duration-150",
                  isDraggingOver ? "border-white bg-zinc-800" : "border-zinc-700"
                )}
              >
                <input ref={fileInputRef} type="file" accept=".pdf,application/pdf" className="hidden"
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) acceptFile(f); }} />
                <Upload className={cn("h-10 w-10", isDraggingOver ? "text-white" : "text-zinc-600")} />
                <p className="mt-4 font-medium text-white">{isDraggingOver ? "Drop it!" : "Drop your PDF here"}</p>
                <p className={cn("mt-1 text-sm", isDraggingOver ? "text-zinc-300" : "text-zinc-500")}>or click to browse</p>
                <p className="mt-3 text-xs text-zinc-600">1 file · Max 100 MB</p>
              </label>

              {selectedFile && (
                <div className="mt-3 flex animate-in slide-in-from-bottom-2 items-center gap-3 rounded-xl bg-zinc-800 p-3 duration-200">
                  <FileText className="h-4 w-4 text-red-400 shrink-0" />
                  <span className="flex-1 truncate text-sm font-medium text-white">{selectedFile.name}</span>
                  <span className="font-mono text-xs text-zinc-500">{formatFileSize(selectedFile.size)}</span>
                  <button onClick={(e) => { e.stopPropagation(); setSelectedFile(null); }} className="rounded-lg p-1 text-zinc-600 transition-colors hover:bg-zinc-700 hover:text-white"><X className="h-4 w-4" /></button>
                </div>
              )}

              <div className="mt-6">
                <DocumateButton className="w-full py-3 text-base" disabled={!selectedFile} onClick={openEditor}>
                  Open Visual Editor <ChevronRight className="h-4 w-4 ml-1" />
                </DocumateButton>
                <p className="mt-2 text-center text-xs text-zinc-600">Pages load in your browser — nothing is uploaded yet</p>
              </div>
            </>
          )}

          {appState === "loading" && (
            <DocumateCard className="mt-6">
              <p className="text-sm text-zinc-400">Rendering page thumbnails...</p>
              <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800">
                <div className="h-full rounded-full bg-white transition-all duration-200" style={{ width: `${loadingProgress}%` }} />
              </div>
              <div className="mt-2 flex items-center justify-between">
                <span className="text-xs text-zinc-600">Processing locally in your browser</span>
                <span className="text-xs text-zinc-600">{loadingProgress}%</span>
              </div>
            </DocumateCard>
          )}

          {appState === "processing" && (
            <DocumateCard className="mt-6">
              <p className="text-sm text-zinc-400">Uploading and splitting your PDF...</p>
              <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-800">
                <div className="h-full rounded-full bg-white animate-pulse w-1/2" />
              </div>
              <p className="mt-2 text-xs text-zinc-600">You will be redirected when complete</p>
            </DocumateCard>
          )}

          <section className="mt-24 border-t border-zinc-800 pt-12">
            <h2 className="mb-8 text-xl font-semibold text-white">How to split a PDF online</h2>
            <div className="space-y-6">
              {steps.map((step, i) => (
                <div key={i} className="flex items-start gap-4">
                  <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-800 font-mono text-xs text-zinc-400">{i + 1}</div>
                  <div><h3 className="font-medium text-white">{step.title}</h3><p className="mt-1 text-sm text-zinc-500">{step.description}</p></div>
                </div>
              ))}
            </div>
          </section>

          <section className="mt-16">
            <h3 className="mb-6 text-lg font-semibold text-white">Frequently asked questions</h3>
            <Accordion type="single" collapsible className="space-y-2">
              {faqs.map((faq, i) => (
                <AccordionItem key={i} value={`faq-${i}`} className="rounded-xl border border-zinc-800 bg-zinc-900 px-5">
                  <AccordionTrigger className="py-4 text-sm font-medium text-white hover:no-underline">{faq.question}</AccordionTrigger>
                  <AccordionContent className="pb-4 text-sm leading-6 text-zinc-400">{faq.answer}</AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          </section>
        </main>
      )}

      {/* ── EDITOR ── */}
      {appState === "editor" && (
        <main className="mx-auto max-w-7xl px-4 py-10">

          {/* First-run help banner */}
          {showFirstRunBanner && (
            <div className="mb-4 flex items-start gap-3 rounded-xl border border-zinc-700 bg-zinc-900 p-4 animate-in fade-in">
              <HelpCircle className="h-5 w-5 text-amber-400 shrink-0 mt-0.5" />
              <div className="flex-1">
                <p className="text-sm font-medium text-white">First time here?</p>
                <p className="text-xs text-zinc-400 mt-0.5">
                  Drag thumbnails to reorder · Click the scissors to split into groups · Trash to remove pages.
                </p>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                <button onClick={openTutorial} className="text-xs text-amber-400 hover:text-amber-300 underline underline-offset-2 transition-colors">
                  Show guide
                </button>
                <button onClick={dismissFirstRun} className="rounded p-1 text-zinc-600 hover:bg-zinc-800 hover:text-white transition-colors">
                  <X className="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          )}

          {/* Toolbar */}
          <div className="mb-6 flex flex-wrap items-center gap-3">
            <div>
              <h1 className="text-xl font-semibold text-white">Visual Page Editor</h1>
              <p className="text-sm text-zinc-500 mt-0.5">
                {selectedFile?.name} &mdash; {activeCount} page{activeCount !== 1 ? "s" : ""} &mdash; {groups.length} group{groups.length !== 1 ? "s" : ""}
              </p>
            </div>
            <div className="ml-auto flex flex-wrap gap-2">
              <button
                onClick={() => setShowTutorial(true)}
                className="flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition-colors"
                title="Show tutorial"
              >
                <HelpCircle className="h-3.5 w-3.5" /> How to use
              </button>
              <button onClick={splitAll} className="flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition-colors">
                <Scissors className="h-3.5 w-3.5" /> Split all
              </button>
              <button onClick={mergeAll} className="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition-colors">
                Merge all
              </button>
              <button onClick={() => { setSelectedFile(null); setPages([]); setAppState("idle"); }} className="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition-colors">
                ← Change file
              </button>
            </div>
          </div>

          {errorMessage && (
            <div className="mb-4 rounded-xl bg-red-950 border border-red-900 p-3 text-sm text-red-400">{errorMessage}</div>
          )}

          {/* Quick legend */}
          <div className="mb-4 flex flex-wrap items-center gap-4 text-xs text-zinc-600">
            <span className="flex items-center gap-1"><Move className="h-3 w-3" /> Drag to reorder</span>
            <span className="flex items-center gap-1"><Scissors className="h-3 w-3" /> Click to split</span>
            <span className="flex items-center gap-1"><Trash2 className="h-3 w-3" /> Click to remove</span>
            <span className="flex items-center gap-1"><RotateCcw className="h-3 w-3" /> Click to restore</span>
          </div>

          {/* Page grid */}
          <div className="flex flex-wrap gap-1 items-end mb-8">
            {pages.map((page, idx) => {
              const gIdx = groupMap.get(page.id) ?? 0;
              const borderCls = GROUP_BORDER[gIdx % GROUP_BORDER.length];
              const barCls    = GROUP_BAR[gIdx % GROUP_BAR.length];
              const isActive  = !page.deleted;
              const isDragging = draggedId === page.id;
              const isTarget   = dragOverId === page.id;
              const showScissors = isActive && idx < pages.length - 1;

              return (
                <div key={page.id} className="flex items-end">
                  <div
                    draggable={isActive}
                    onDragStart={(e) => onDragStart(e, page.id)}
                    onDragOver={(e) => onDragOver(e, page.id)}
                    onDrop={(e) => onDropPage(e, page.id)}
                    onDragEnd={() => { setDraggedId(null); setDragOverId(null); }}
                    className={cn(
                      "relative w-24 rounded-lg overflow-hidden border-2 select-none transition-all duration-150",
                      isActive ? cn("cursor-grab", borderCls) : "cursor-default border-dashed border-zinc-700 opacity-40 grayscale",
                      isDragging && "opacity-20 scale-95",
                      isTarget && isActive && "ring-2 ring-white ring-offset-1 ring-offset-zinc-950"
                    )}
                  >
                    {isActive && <div className={cn("h-1 w-full", barCls)} />}
                    <div className="bg-zinc-800">
                      <img src={page.thumbnail} alt={`Page ${page.pageNum}`} className="w-full object-contain" draggable={false} />
                    </div>
                    <div className="bg-zinc-900 text-center py-1 text-xs text-zinc-400 font-mono">{page.pageNum}</div>
                    {isActive && <div className="absolute top-1.5 left-1 opacity-40"><GripVertical className="h-3 w-3 text-zinc-400" /></div>}
                    <div className="absolute top-1.5 right-1">
                      {page.deleted ? (
                        <button onClick={() => toggleDelete(page.id)} title="Restore page" className="rounded bg-zinc-900/80 p-0.5 hover:bg-zinc-700 transition-colors">
                          <RotateCcw className="h-3 w-3 text-emerald-400" />
                        </button>
                      ) : (
                        <button onClick={() => toggleDelete(page.id)} title="Remove page" className="rounded bg-zinc-900/80 p-0.5 hover:bg-zinc-700 transition-colors">
                          <Trash2 className="h-3 w-3 text-red-400" />
                        </button>
                      )}
                    </div>
                  </div>

                  {showScissors && (
                    <button
                      onClick={() => toggleSplit(page.id)}
                      title={page.splitAfter ? "Remove split" : "Split here"}
                      className={cn(
                        "mx-0.5 mb-7 flex flex-col items-center gap-0.5 group",
                        page.splitAfter ? "text-white" : "text-zinc-700 hover:text-zinc-400"
                      )}
                    >
                      <div className={cn("w-px h-5 transition-colors", page.splitAfter ? "bg-white" : "bg-zinc-700 group-hover:bg-zinc-500")} />
                      <Scissors className="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
                      <div className={cn("w-px h-5 transition-colors", page.splitAfter ? "bg-white" : "bg-zinc-700 group-hover:bg-zinc-500")} />
                    </button>
                  )}
                </div>
              );
            })}
          </div>

          {/* Groups preview */}
          {groups.length > 0 && (
            <div className="mb-6 rounded-xl border border-zinc-800 bg-zinc-900 p-4">
              <p className="text-xs font-medium text-zinc-400 mb-3 uppercase tracking-wider">Output preview — {groups.length} PDF{groups.length !== 1 ? "s" : ""} in ZIP</p>
              <div className="flex flex-wrap gap-2">
                {groups.map((g, i) => {
                  const dotCls = GROUP_DOT[i % GROUP_DOT.length];
                  const pageNums = g.join(", ");
                  return (
                    <div key={i} className="flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-1.5 text-sm">
                      <div className={cn("h-2 w-2 rounded-full shrink-0", dotCls)} />
                      <span className="text-zinc-300 font-medium">Part {i + 1}</span>
                      <span className="text-zinc-500 text-xs">pages {pageNums}</span>
                      <span className="text-zinc-600 text-xs">({g.length}p)</span>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          <DocumateButton onClick={handleSubmit} className="w-full py-3 text-base">
            Split &amp; Download ZIP &mdash; {groups.length} file{groups.length !== 1 ? "s" : ""}
          </DocumateButton>
          <p className="mt-2 text-center text-xs text-zinc-600">Your file will be uploaded and processed on the server</p>
        </main>
      )}

      <Footer />
    </div>
  );
}
