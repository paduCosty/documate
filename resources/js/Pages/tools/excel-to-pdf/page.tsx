import { ToolLayout } from "@/components/documate/tool-layout"

const faqs = [
  {
    question: "Which Excel formats are supported?",
    answer: "We support .xls (Excel 97-2003) and .xlsx (Excel 2007 and later) formats. Both are converted with accurate formatting to PDF.",
  },
  {
    question: "How are multiple sheets handled?",
    answer: "Each sheet in your Excel workbook becomes a separate page (or pages) in the PDF. Sheet names are preserved as page headers if you enable that option.",
  },
  {
    question: "Will my formulas be visible?",
    answer: "The PDF shows the calculated values, not the formulas. If you want to show formulas, you can enable 'Show Formulas' in Excel before saving, then convert that version.",
  },
  {
    question: "What about charts and images?",
    answer: "Charts, images, and other graphical elements are fully preserved in the PDF conversion. They appear exactly as they do in Excel.",
  },
  {
    question: "Can I control the page layout?",
    answer: "The PDF uses the print settings from your Excel file. For best results, set up your print area and page orientation in Excel before converting.",
  },
]

const steps = [
  {
    title: "Upload your Excel file",
    description: "Drag and drop your .xls or .xlsx file into the upload area, or click to browse and select your spreadsheet.",
  },
  {
    title: "Automatic conversion",
    description: "We convert your spreadsheet to PDF, preserving cell formatting, formulas results, charts, and images.",
  },
  {
    title: "Download your PDF",
    description: "Download your converted PDF. All sheets are included as separate pages in the document.",
  },
]

export default function ExcelToPdfPage() {
  return (
    <ToolLayout
      toolName="Excel to PDF"
      toolDescription="Turn spreadsheets into perfectly formatted PDF documents."
      acceptedFormats={[".xls", ".xlsx", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"]}
      maxFiles={10}
      maxSizeMB={10}
      faqs={faqs}
      steps={steps}
      actionButtonText="Convert to PDF"
      outputFileName="spreadsheet.pdf"
      dropzoneText="Drop your Excel spreadsheets here"
      toolRoute="/tools/excel-to-pdf"
    />
  )
}
