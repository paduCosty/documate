import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "How many PDFs can I merge at once?",
    answer: "Free users can merge up to 10 PDF files at once. Pro and Business users have no limit on the number of files they can merge in a single operation.",
  },
  {
    question: "Will merging PDFs reduce the quality?",
    answer: "No, merging PDFs with Documate does not reduce quality. We combine your files without any re-compression, so your images, text, and formatting remain exactly as they were in the original files.",
  },
  {
    question: "Can I rearrange pages before merging?",
    answer: "Yes! After uploading your files, you can drag and drop them to change the order. The final merged PDF will follow the sequence you set.",
  },
  {
    question: "Is there a file size limit?",
    answer: "Free users can upload files up to 10MB each. Pro users have a 100MB limit, and Business users can upload files up to 500MB each.",
  },
  {
    question: "Are my files secure?",
    answer: "Absolutely. All uploads are encrypted with 256-bit SSL encryption. Your files are automatically deleted from our servers after 24 hours, and we never share or access your document contents.",
  },
]

const steps = [
  {
    title: "Upload your PDF files",
    description: "Drag and drop your PDF files into the upload area, or click to browse your computer. You can select multiple files at once.",
  },
  {
    title: "Arrange the order",
    description: "Drag the files to rearrange them in the order you want them to appear in the final merged document.",
  },
  {
    title: "Merge and download",
    description: "Click the Merge button and wait a few seconds. Once complete, download your merged PDF file.",
  },
]

export default function MergePdfPage() {
  return (
    <ToolLayout
      toolName="Merge PDF"
      toolDescription="Combine multiple PDF files into one document online for free. Drag to reorder files before merging. No signup required. Files deleted after 24 hours."
      acceptedFormats={[".pdf", "application/pdf"]}
      maxFiles={10}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Merge PDFs"
      outputFileName="merged.pdf"
      minFiles={2}
      toolRoute="/tools/merge-pdf"
    />
  )
}
