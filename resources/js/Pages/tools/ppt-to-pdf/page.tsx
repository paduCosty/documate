import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "Which PowerPoint formats are supported?",
    answer: "We support .ppt (PowerPoint 97-2003) and .pptx (PowerPoint 2007 and later) formats. Both are converted with full visual fidelity.",
  },
  {
    question: "Are animations and transitions preserved?",
    answer: "PDF is a static format, so animations and transitions cannot be preserved. The final state of each slide is captured in the PDF.",
  },
  {
    question: "What about speaker notes?",
    answer: "By default, only slides are included. If you need speaker notes, you can export from PowerPoint with the 'Notes Pages' layout before converting.",
  },
  {
    question: "Will embedded videos be included?",
    answer: "Videos cannot be embedded in PDF files. A placeholder image or the first frame of the video will appear in the PDF instead.",
  },
  {
    question: "How is the slide quality?",
    answer: "Slides are converted at high resolution, suitable for printing and professional presentations. Text, shapes, and images maintain their original quality.",
  },
]

const steps = [
  {
    title: "Upload your presentation",
    description: "Drag and drop your .ppt or .pptx file into the upload area, or click to browse your files.",
  },
  {
    title: "Automatic conversion",
    description: "Each slide is converted to a high-quality PDF page, preserving all visual elements, fonts, and formatting.",
  },
  {
    title: "Download your PDF",
    description: "Download your converted presentation as a PDF. Each slide becomes a page in the document.",
  },
]

export default function PptToPdfPage() {
  return (
    <ToolLayout
      toolName="PPT to PDF"
      toolDescription="Convert PowerPoint presentations to PDF with all slides intact."
      acceptedFormats={[".ppt", ".pptx", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation"]}
      maxFiles={10}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Convert to PDF"
      outputFileName="presentation.pdf"
    />
  )
}
