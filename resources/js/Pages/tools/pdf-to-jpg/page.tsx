import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "What image quality can I expect?",
    answer: "We convert at 300 DPI by default, which is suitable for printing. Pro users can choose up to 600 DPI for maximum quality.",
  },
  {
    question: "How many pages can I convert?",
    answer: "Each page becomes a separate JPG image. Free users can convert PDFs up to 20 pages. Pro and Business users have no page limit.",
  },
  {
    question: "Can I convert to other image formats?",
    answer: "Currently we support JPG output, which offers the best balance of quality and file size. PNG support is coming soon for images requiring transparency.",
  },
  {
    question: "How are the images named?",
    answer: "Images are named sequentially: page-1.jpg, page-2.jpg, etc. They're delivered in a zip file for easy download.",
  },
  {
    question: "Will text be readable in the images?",
    answer: "Yes, at 300 DPI text is crisp and readable even when zoomed in. For small text or detailed documents, we recommend the 600 DPI option.",
  },
]

const steps = [
  {
    title: "Upload your PDF",
    description: "Drag and drop your PDF file into the upload area, or click to browse and select your document.",
  },
  {
    title: "Automatic conversion",
    description: "Each page of your PDF is converted to a high-quality JPG image at 300 DPI resolution.",
  },
  {
    title: "Download your images",
    description: "Download all converted images as a zip file. Each page is saved as a separate JPG image.",
  },
]

export default function PdfToJpgPage() {
  return (
    <ToolLayout
      toolName="PDF to JPG"
      toolDescription="Convert each PDF page into a high-quality JPG image."
      acceptedFormats={[".pdf", "application/pdf"]}
      maxFiles={1}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Convert to JPG"
      outputFileName="images.zip"
    />
  )
}
