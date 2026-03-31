import { Link } from "@inertiajs/react"
import { Mail, Clock } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"

const subjects = [
  { value: "general", label: "General" },
  { value: "billing", label: "Billing" },
  { value: "technical", label: "Technical" },
  { value: "feature", label: "Feature request" },
]

export default function ContactPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-4xl px-6 py-24">
        <h1 className="text-4xl font-bold tracking-tight text-white">Get in touch</h1>
        <p className="mt-4 text-zinc-500">Have a question or feedback? We&apos;d love to hear from you.</p>

        <div className="mt-16 grid grid-cols-1 gap-12 md:grid-cols-[1fr_320px]">
          {/* Contact Form */}
          <DocumateCard>
            <form className="space-y-5">
              <DocumateInput label="Name" placeholder="Your name" />
              <DocumateInput label="Email" type="email" placeholder="you@example.com" />
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-zinc-500">Subject</label>
                <select className="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white focus:border-zinc-600 focus:outline-none">
                  {subjects.map((subject) => (
                    <option key={subject.value} value={subject.value}>{subject.label}</option>
                  ))}
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-zinc-500">Message</label>
                <textarea
                  placeholder="How can we help?"
                  rows={5}
                  className="w-full resize-none rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-zinc-600 focus:outline-none"
                />
              </div>
              <DocumateButton type="submit">Send message</DocumateButton>
            </form>
          </DocumateCard>

          {/* Info Card */}
          <DocumateCard className="h-fit space-y-6">
            <div className="flex items-start gap-3">
              <Mail className="mt-0.5 h-5 w-5 text-zinc-500" />
              <div>
                <span className="text-sm text-white">support@documate.io</span>
              </div>
            </div>
            <div className="flex items-start gap-3">
              <Clock className="mt-0.5 h-5 w-5 text-zinc-500" />
              <div>
                <span className="text-sm text-zinc-400">We reply within 24 hours</span>
                <DocumateBadge variant="success" className="ml-2">Usually faster</DocumateBadge>
              </div>
            </div>
            <div className="space-y-2 border-t border-zinc-800 pt-6">
              <Link href="/faq" className="block text-sm text-zinc-500 transition-colors hover:text-white">
                Browse FAQ &rarr;
              </Link>
              <Link href="/tools" className="block text-sm text-zinc-500 transition-colors hover:text-white">
                View all tools &rarr;
              </Link>
            </div>
          </DocumateCard>
        </div>
      </main>

      <Footer />
    </div>
  )
}
