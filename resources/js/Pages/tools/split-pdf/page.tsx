import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "Can I extract specific pages from a PDF?",
    answer: "Yes! After uploading your PDF, you can select exactly which pages you want to extract. You can pick individual pages or ranges like '1-3, 5, 7-10'.",
  },
  {
    question: "Can I split a PDF into multiple files?",
    answer: "Yes, you can split a PDF into multiple separate files. Choose to split every N pages, or manually specify where to split the document.",
  },
  {
    question: "Will splitting affect the quality?",
    answer: "No, splitting a PDF does not affect quality. We extract pages without any re-compression, so your content remains exactly as it was.",
  },
  {
    question: "Is there a page limit?",
    answer: "Free users can work with PDFs up to 100 pages. Pro users have a 500-page limit, and Business users have no limit.",
  },
  {
    question: "Can I preview pages before splitting?",
    answer: "Yes! Our visual page picker shows thumbnail previews of each page, making it easy to select exactly what you need.",
  },
]

const steps = [
  {
    title: "Upload your PDF",
    description: "Drag and drop your PDF file into the upload area, or click to browse your computer.",
  },
  {
    title: "Select pages to extract",
    description: "Use our visual page picker to select which pages you want. Click individual pages or specify page ranges.",
  },
  {
    title: "Download split files",
    description: "Download your extracted pages as a new PDF file, or download multiple split files as a zip archive.",
  },
]

export default function SplitPdfPage() {
  return (
    <ToolLayout
      toolName="Split PDF"
      toolDescription="Extract specific pages or split into multiple files. Visual page picker."
      acceptedFormats={[".pdf", "application/pdf"]}
      maxFiles={1}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Split PDF"
      outputFileName="split.pdf"
    />
  )
}
