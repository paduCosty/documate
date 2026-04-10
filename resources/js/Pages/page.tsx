import {Link} from "@inertiajs/react";
import { SEOHead } from "@/components/documate/seo-head";
import { GitMerge, Minimize2, FileText, Table, Presentation, Scissors, Image, ShieldCheck, Clock, Zap, Check, Star } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"

const tools = [
  { name: "Merge PDF", description: "Combine multiple PDFs into one file. Drag to reorder pages.", icon: GitMerge, href: "/tools/merge-pdf" },
  { name: "Compress PDF", description: "Reduce PDF file size without losing quality. See before/after size.", icon: Minimize2, href: "/tools/compress-pdf" },
  { name: "Word to PDF", description: "Convert .doc and .docx files to PDF instantly.", icon: FileText, href: "/tools/word-to-pdf" },
  { name: "Excel to PDF", description: "Turn spreadsheets into perfectly formatted PDF documents.", icon: Table, href: "/tools/excel-to-pdf" },
  { name: "PPT to PDF", description: "Convert PowerPoint presentations to PDF with all slides intact.", icon: Presentation, href: "/tools/ppt-to-pdf" },
  { name: "Split PDF", description: "Extract specific pages or split into multiple files. Visual page picker.", icon: Scissors, href: "/tools/split-pdf" },
  { name: "PDF to JPG", description: "Convert each PDF page into a high-quality JPG image.", icon: Image, href: "/tools/pdf-to-jpg" },
]

const features = [
  { name: "256-bit encryption", description: "All uploads are encrypted in transit and at rest.", icon: ShieldCheck },
  { name: "Auto-deleted in 24h", description: "Your files are permanently removed after 24 hours. Always.", icon: Clock },
  { name: "Processed in seconds", description: "No queue. No waiting. Results ready instantly.", icon: Zap },
]

const testimonials = [
  { quote: "Finally, a PDF tool that doesn't feel like it's from 2005. Clean, fast, and just works.", author: "Sarah Chen", role: "Product Designer", initials: "SC" },
  { quote: "We switched from Adobe to Documate for our team. Saved hours every week on document processing.", author: "Marcus Johnson", role: "Operations Manager", initials: "MJ" },
  { quote: "The compression is incredible. Our contracts went from 15MB to under 2MB without losing quality.", author: "Elena Rodriguez", role: "Legal Counsel", initials: "ER" },
]

export default function HomePage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Documate — Free Online PDF Tools"
        description="Merge, compress, split, and convert PDF files online for free. Fast, secure, no registration required. Files auto-deleted in 24 hours."
        canonical="https://documate.nexkit.app"
      />
      <Navbar />

      {/* Hero Section */}
      <section className="relative flex min-h-[90vh] flex-col items-center justify-center px-6 py-24">
        {/* Dot Pattern Background */}
        <div className="pointer-events-none absolute inset-0 opacity-40" style={{
          backgroundImage: "radial-gradient(circle, #27272a 1px, transparent 1px)",
          backgroundSize: "24px 24px",
          maskImage: "radial-gradient(ellipse at center, black 0%, transparent 70%)",
          WebkitMaskImage: "radial-gradient(ellipse at center, black 0%, transparent 70%)"
        }} />

        <div className="relative z-10 mx-auto max-w-4xl text-center">
          {/* Pill Badge */}
          <div className="mx-auto mb-8 inline-flex items-center gap-2 rounded-full border border-zinc-700 bg-zinc-800 px-4 py-1.5 text-xs text-zinc-400">
            <span className="text-amber-400">&#10022;</span>
            Free for basic tools — no account needed
          </div>

          {/* Headline */}
          <h1 className="mx-auto max-w-3xl text-balance text-5xl font-bold tracking-[-0.03em] text-white md:text-7xl">
            Your PDF toolkit,<br />finally done right.
          </h1>

          {/* Subtitle */}
          <p className="mx-auto mt-6 max-w-xl text-pretty text-lg leading-7 text-zinc-400">
            Merge, compress, convert, and split PDF files in seconds. No signup. No watermarks. Files deleted after 24 hours.
          </p>

          {/* CTA Buttons */}
          <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
            <Link href="/tools/merge-pdf">
              <DocumateButton size="lg">Start for free</DocumateButton>
            </Link>
            <Link href="/tools">
              <DocumateButton variant="ghost" size="lg">View all tools &rarr;</DocumateButton>
            </Link>
          </div>

          {/* Social Proof */}
          <div className="mt-12 flex flex-wrap items-center justify-center gap-3">
            <div className="flex -space-x-2">
              {["AJ", "MK", "SR"].map((initials, i) => (
                <div key={i} className="flex h-8 w-8 items-center justify-center rounded-xl bg-zinc-800 text-xs font-semibold text-white ring-2 ring-zinc-950">
                  {initials}
                </div>
              ))}
            </div>
            <span className="text-sm text-zinc-500">Joined by 50,000+ users</span>
            <div className="flex gap-0.5">
              {[...Array(5)].map((_, i) => (
                <Star key={i} className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Tools Grid Section */}
      <section className="pb-24 pt-32">
        <div className="mx-auto max-w-6xl px-6">
          <div className="text-center">
            <span className="text-xs font-medium uppercase tracking-widest text-zinc-600">TOOLS</span>
            <h2 className="mb-4 mt-4 text-3xl font-semibold text-white">Everything you need for PDF</h2>
            <p className="mb-16 text-zinc-500">7 powerful tools. All free to start.</p>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {tools.map((tool) => (
              <Link key={tool.name} href={tool.href}>
                <DocumateCard hover className="h-full">
                  <div className="flex items-center gap-3">
                    <div className="rounded-xl bg-zinc-800 p-2.5">
                      <tool.icon className="h-5 w-5 text-zinc-400" />
                    </div>
                    <span className="font-semibold text-white">{tool.name}</span>
                  </div>
                  <p className="mt-3 text-sm leading-6 text-zinc-500">{tool.description}</p>
                  <span className="mt-4 block text-xs text-zinc-400 transition-colors hover:text-white">Use tool &rarr;</span>
                </DocumateCard>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Features Strip */}
      <section className="border-y border-zinc-800/50 bg-zinc-950 py-24">
        <div className="mx-auto grid max-w-4xl grid-cols-1 gap-8 px-6 md:grid-cols-3">
          {features.map((feature) => (
            <div key={feature.name}>
              <div className="w-fit rounded-xl bg-zinc-800 p-2.5">
                <feature.icon className="h-5 w-5 text-zinc-600" />
              </div>
              <h3 className="mt-4 font-semibold text-white">{feature.name}</h3>
              <p className="mt-2 text-sm leading-6 text-zinc-500">{feature.description}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Pricing Teaser */}
      <section className="py-32">
        <div className="mx-auto max-w-5xl px-6">
          <div className="text-center">
            <span className="text-xs font-medium uppercase tracking-widest text-zinc-600">PRICING</span>
            <h2 className="mb-4 mt-4 text-3xl font-semibold text-white">Simple pricing</h2>
            <p className="mb-16 text-zinc-500">Start free. Upgrade when you need more.</p>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            {/* Free Plan */}
            <DocumateCard>
              <DocumateBadge>Free</DocumateBadge>
              <div className="mt-4">
                <span className="text-4xl font-bold text-white">&euro;0</span>
                <span className="text-sm text-zinc-500">/month</span>
              </div>
              <ul className="mt-6 space-y-3">
                {["3 operations/day", "10MB max file size", "Basic tools", "No account needed"].map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 text-zinc-600" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register" className="mt-6 block">
                <DocumateButton variant="outline" className="w-full">Get started</DocumateButton>
              </Link>
            </DocumateCard>

            {/* Pro Plan */}
            <div className="relative rounded-2xl border-2 border-white bg-zinc-900 p-6 shadow-[0_0_40px_rgba(255,255,255,0.06)]">
              <DocumateBadge variant="pro" className="absolute right-4 top-4">Most Popular</DocumateBadge>
              <span className="text-sm font-medium text-zinc-400">Pro</span>
              <div className="mt-2">
                <span className="text-4xl font-bold text-white">&euro;7</span>
                <span className="text-sm text-zinc-500">/month</span>
              </div>
              <ul className="mt-6 space-y-3">
                {["Unlimited operations", "100MB max file size", "All tools", "Sign PDF", "OCR", "30-day history"].map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 text-[#22c55e]" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register?plan=pro" className="mt-6 block">
                <DocumateButton className="w-full">Get Pro &rarr;</DocumateButton>
              </Link>
            </div>

            {/* Business Plan */}
            <DocumateCard>
              <span className="text-sm font-medium text-zinc-400">Business</span>
              <div className="mt-2">
                <span className="text-4xl font-bold text-white">&euro;19</span>
                <span className="text-sm text-zinc-500">/month</span>
              </div>
              <ul className="mt-6 space-y-3">
                {["Everything in Pro", "500MB max file size", "AI Chat with PDF", "Translate PDF", "API access", "1-year history"].map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 text-[#22c55e]" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register?plan=business" className="mt-6 block">
                <DocumateButton variant="outline" className="w-full">Get Business</DocumateButton>
              </Link>
            </DocumateCard>
          </div>

          <div className="mt-8 text-center">
            <Link href="/pricing" className="text-sm text-zinc-500 transition-colors hover:text-white">
              See full pricing &rarr;
            </Link>
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="bg-zinc-950 py-24">
        <div className="mx-auto max-w-5xl px-6">
          <h2 className="mb-12 text-center text-3xl font-semibold text-white">Trusted by teams worldwide</h2>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            {testimonials.map((testimonial) => (
              <DocumateCard key={testimonial.author}>
                <div className="flex gap-0.5">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                  ))}
                </div>
                <p className="mt-4 text-sm italic leading-6 text-zinc-400">&ldquo;{testimonial.quote}&rdquo;</p>
                <div className="mt-6 flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-800 text-sm font-semibold text-white">
                    {testimonial.initials}
                  </div>
                  <div>
                    <div className="text-sm text-white">{testimonial.author}</div>
                    <div className="text-xs text-zinc-600">{testimonial.role}</div>
                  </div>
                </div>
              </DocumateCard>
            ))}
          </div>
        </div>
      </section>

      {/* Final CTA Banner */}
      <section className="border-y border-zinc-800 bg-zinc-900 py-24">
        <div className="mx-auto max-w-4xl px-6 text-center">
          <h2 className="text-3xl font-bold text-white">Start processing PDFs for free.</h2>
          <p className="mt-4 text-zinc-500">No credit card required. No watermarks. Files auto-deleted.</p>
          <Link href="/tools/merge-pdf" className="mt-8 inline-block">
            <DocumateButton size="lg">Get started — it&apos;s free</DocumateButton>
          </Link>
        </div>
      </section>

      <Footer />
    </div>
  )
}
