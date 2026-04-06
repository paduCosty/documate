import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "How much can I reduce my PDF file size?",
    answer: "Compression results vary depending on your PDF content. Documents with many images typically see 40-80% reduction. Text-heavy documents may see 10-30% reduction. We always show you the before and after sizes.",
  },
  {
    question: "Will compression affect the quality of my PDF?",
    answer: "Our smart compression algorithm optimizes file size while maintaining visual quality. For most uses, the difference is imperceptible. We use industry-standard compression techniques that preserve text clarity and image quality.",
  },
  {
    question: "What types of PDFs compress best?",
    answer: "PDFs with high-resolution images, scanned documents, and presentations typically compress the most. Text-only PDFs are already quite efficient, so they may see smaller reductions.",
  },
  {
    question: "Is the compressed PDF still searchable?",
    answer: "Yes, if your original PDF had searchable text, the compressed version will retain that functionality. Our compression targets image data, not text content.",
  },
]

const steps = [
  {
    title: "Upload your PDF",
    description: "Select the PDF file you want to compress by dragging it into the upload area or clicking to browse.",
  },
  {
    title: "Automatic compression",
    description: "Our system automatically analyzes your PDF and applies optimal compression settings to reduce file size.",
  },
  {
    title: "Download compressed file",
    description: "Review the size reduction and download your compressed PDF. The original quality is preserved.",
  },
]

export default function CompressPdfPage() {
  return (
    <ToolLayout
      toolName="Compress PDF"
      toolDescription="Reduce PDF file size without losing quality. See before and after sizes."
      acceptedFormats={[".pdf", "application/pdf"]}
      maxFiles={1}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Compress PDF"
      outputFileName="compressed.pdf"
      toolRoute="/tools/compress-pdf"
    />
  )
}
