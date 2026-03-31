"use client"

import { useState } from "react"
import { Search } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"

const categories = ["All", "General", "Pricing", "Security", "Tools", "Account"]

const faqs = [
  { category: "General", question: "What is Documate?", answer: "Documate is an online PDF toolkit that lets you merge, compress, convert, and split PDF files in seconds. It's designed to be fast, secure, and easy to use—no software installation required." },
  { category: "General", question: "Do I need to create an account?", answer: "No, you can use basic tools without an account. Creating a free account gives you 10 operations per day instead of 3, and paid plans offer unlimited operations and additional features." },
  { category: "General", question: "What file formats do you support?", answer: "We support PDF files for most operations, plus Word (.doc, .docx), Excel (.xls, .xlsx), and PowerPoint (.ppt, .pptx) for conversion to PDF. PDF to JPG converts PDF pages to high-quality images." },
  { category: "Pricing", question: "Is there a free plan?", answer: "Yes! Our free plan includes 3 operations per day with a 10MB file size limit. It's completely free forever—no credit card required." },
  { category: "Pricing", question: "Can I cancel my subscription anytime?", answer: "Absolutely. You can cancel your subscription at any time from your billing settings. Your access continues until the end of your current billing period." },
  { category: "Pricing", question: "Do you offer refunds?", answer: "Yes, we offer a 30-day money-back guarantee on all paid plans. If you're not satisfied, contact our support team for a full refund." },
  { category: "Security", question: "Are my files secure?", answer: "Yes. All files are encrypted with 256-bit SSL during upload and processing. Files are automatically deleted from our servers after 24 hours (or up to 1 year for Business users who opt-in to file history)." },
  { category: "Security", question: "Who can access my files?", answer: "Only you can access your files. We never share, sell, or access your document contents. Our processing is automated and your files remain private." },
  { category: "Security", question: "Where are files stored?", answer: "Files are temporarily stored on secure servers in the EU and US. They're encrypted at rest and in transit, and automatically deleted after processing." },
  { category: "Tools", question: "How does PDF compression work?", answer: "Our compression algorithm optimizes images and internal PDF structures to reduce file size while maintaining visual quality. Results vary by document—image-heavy PDFs typically see 40-80% size reduction." },
  { category: "Tools", question: "Can I merge more than 10 files?", answer: "Free users can merge up to 10 files at once. Pro and Business users have no limit on the number of files per operation." },
  { category: "Tools", question: "Is OCR available?", answer: "Yes, OCR (Optical Character Recognition) is available on Pro and Business plans. It converts scanned documents and images into searchable, selectable text." },
  { category: "Account", question: "How do I upgrade my plan?", answer: "Go to Dashboard > Billing and click 'Upgrade'. Choose your plan, enter payment details, and your upgrade is instant." },
  { category: "Account", question: "Can I change my email address?", answer: "Yes, go to Dashboard > Settings > Profile to update your email. You'll need to verify your new email address before the change takes effect." },
  { category: "Account", question: "How do I delete my account?", answer: "Go to Dashboard > Settings > Danger Zone and click 'Delete account'. This permanently removes all your data and cannot be undone." },
]

export default function FaqPage() {
  const [searchQuery, setSearchQuery] = useState("")
  const [activeCategory, setActiveCategory] = useState("All")

  const filteredFaqs = faqs.filter((faq) => {
    const matchesSearch =
      faq.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
      faq.answer.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesCategory = activeCategory === "All" || faq.category === activeCategory
    return matchesSearch && matchesCategory
  })

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-3xl px-6 py-24">
        <h1 className="text-4xl font-bold tracking-tight text-white">Frequently asked questions</h1>
        <p className="mt-4 text-zinc-500">Find answers to common questions about Documate.</p>

        {/* Search */}
        <div className="relative mt-8">
          <Search className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
          <input
            type="text"
            placeholder="Search questions..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full rounded-xl border border-zinc-800 bg-zinc-900 py-4 pl-12 pr-4 text-white placeholder:text-zinc-500 focus:border-zinc-600 focus:outline-none"
          />
        </div>

        {/* Category Tabs */}
        <div className="mt-10 border-b border-zinc-800">
          <div className="flex gap-6 overflow-x-auto">
            {categories.map((category) => (
              <button
                key={category}
                onClick={() => setActiveCategory(category)}
                className={`whitespace-nowrap border-b-2 pb-3 text-sm transition-colors ${
                  activeCategory === category
                    ? "border-white text-white"
                    : "border-transparent text-zinc-500 hover:text-white"
                }`}
              >
                {category}
              </button>
            ))}
          </div>
        </div>

        {/* FAQ List */}
        <Accordion type="single" collapsible className="mt-8 space-y-2">
          {filteredFaqs.map((faq, index) => (
            <AccordionItem
              key={index}
              value={`faq-${index}`}
              className="rounded-xl border border-zinc-800 bg-zinc-900 px-5"
            >
              <AccordionTrigger className="py-4 text-left text-sm font-medium text-white hover:no-underline">
                {faq.question}
              </AccordionTrigger>
              <AccordionContent className="pb-4 text-sm leading-7 text-zinc-400">
                {faq.answer}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>

        {filteredFaqs.length === 0 && (
          <div className="mt-16 text-center">
            <p className="text-zinc-500">No questions found matching your search.</p>
          </div>
        )}
      </main>

      <Footer />
    </div>
  )
}
