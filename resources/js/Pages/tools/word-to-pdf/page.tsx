import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "Which Word formats are supported?",
    answer: "We support both .doc (Word 97-2003) and .docx (Word 2007 and later) formats. Both formats are converted with full fidelity to PDF.",
  },
  {
    question: "Will my formatting be preserved?",
    answer: "Yes, our converter preserves fonts, images, tables, headers, footers, and most formatting. Complex features like macros are not preserved, but all visual elements remain intact.",
  },
  {
    question: "Can I convert multiple Word documents at once?",
    answer: "Yes! Upload multiple Word files and they will all be converted to PDF. You can download them individually or as a zip file.",
  },
  {
    question: "Do I need Microsoft Word installed?",
    answer: "No, our conversion happens entirely in the cloud. You don't need any software installed on your computer.",
  },
  {
    question: "What about embedded fonts?",
    answer: "Embedded fonts in your Word document will be preserved in the PDF. If a font isn't embedded, we substitute a similar system font.",
  },
]

const steps = [
  {
    title: "Upload your Word document",
    description: "Drag and drop your .doc or .docx file into the upload area, or click to browse your files.",
  },
  {
    title: "Automatic conversion",
    description: "Our system converts your Word document to PDF while preserving all formatting, images, and fonts.",
  },
  {
    title: "Download your PDF",
    description: "Once conversion is complete, download your new PDF file. It's ready to share or print.",
  },
]

export default function WordToPdfPage() {
  return (
    <ToolLayout
      toolName="Word to PDF"
      toolDescription="Convert .doc and .docx files to PDF instantly with perfect formatting."
      acceptedFormats={[".doc", ".docx", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"]}
      maxFiles={10}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Convert to PDF"
      outputFileName="document.pdf"
      dropzoneText="Drop your Word documents here"
      toolRoute="/tools/word-to-pdf"
    />
  )
}
