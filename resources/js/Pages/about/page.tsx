import { Link } from "@inertiajs/react"
import { SEOHead } from "@/components/documate/seo-head"
import { Shield, Zap, Globe, Lock } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"

const values = [
  {
    icon: Zap,
    title: "Speed first",
    description: "Every tool is built to process files in seconds, not minutes. No waiting, no queues for basic operations.",
  },
  {
    icon: Lock,
    title: "Privacy by design",
    description: "Your files are processed on our servers and deleted automatically within 24 hours. We never read or store your documents.",
  },
  {
    icon: Globe,
    title: "No installation",
    description: "Everything runs in your browser. No desktop apps, no plugins, no account required to get started.",
  },
  {
    icon: Shield,
    title: "Transparent pricing",
    description: "Three simple options: a free tier that never expires, credits for occasional use, and a flat Pro subscription for power users.",
  },
]

export default function AboutPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="About — Documate"
        description="Documate is a fast, secure, and free online PDF toolkit built for teams and individuals. No registration required."
        canonical="/about"
      />
      <Navbar />

      <main>
        {/* Hero */}
        <section className="mx-auto max-w-3xl px-6 py-24 text-center">
          <h1 className="text-4xl font-bold tracking-tight text-white md:text-5xl">
            PDF tools that just work
          </h1>
          <p className="mx-auto mt-6 max-w-xl text-lg text-zinc-400">
            Documate is a simple, fast, and private toolkit for everyday PDF tasks —
            built for people who need results without the friction.
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-3">
            <Link
              href="/tools"
              className="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100"
            >
              Try the tools
            </Link>
            <Link
              href="/contact"
              className="rounded-xl border border-zinc-700 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-zinc-800"
            >
              Get in touch
            </Link>
          </div>
        </section>

        {/* Story */}
        <section className="border-t border-zinc-800 bg-zinc-900/30">
          <div className="mx-auto max-w-3xl px-6 py-20">
            <h2 className="text-2xl font-semibold text-white">Why we built this</h2>
            <div className="mt-6 space-y-4 text-sm leading-7 text-zinc-400">
              <p>
                Every developer and office worker has been there — you need to merge two PDFs,
                compress a file before emailing it, or convert a Word document. You search for
                a tool online, and the first result is a bloated site plastered with ads,
                requiring sign-up, and uploading your documents to who-knows-where.
              </p>
              <p>
                We built Documate to be the opposite of that. Fast, clean, and honest about
                what happens to your files. The free tier covers 90% of what most people need,
                and you never have to hand over your email to try it.
              </p>
              <p>
                For teams and power users who need more — more operations, larger files,
                priority processing — Pro is a straightforward flat-rate subscription.
                No per-file charges, no surprise bills.
              </p>
            </div>
          </div>
        </section>

        {/* Values */}
        <section className="mx-auto max-w-4xl px-6 py-20">
          <h2 className="text-2xl font-semibold text-white">What we stand for</h2>
          <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
            {values.map((v) => {
              const Icon = v.icon
              return (
                <DocumateCard key={v.title}>
                  <div className="flex items-start gap-4">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-800">
                      <Icon className="h-5 w-5 text-zinc-300" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-white">{v.title}</h3>
                      <p className="mt-1 text-sm text-zinc-400">{v.description}</p>
                    </div>
                  </div>
                </DocumateCard>
              )
            })}
          </div>
        </section>

        {/* Numbers */}
        <section className="border-t border-zinc-800 bg-zinc-900/30">
          <div className="mx-auto max-w-4xl px-6 py-20">
            <div className="grid grid-cols-2 gap-8 md:grid-cols-4 text-center">
              {[
                { value: "7", label: "PDF tools" },
                { value: "24h", label: "File retention" },
                { value: "100%", label: "Browser-based" },
                { value: "Free", label: "To start" },
              ].map((s) => (
                <div key={s.label}>
                  <p className="text-3xl font-bold text-white">{s.value}</p>
                  <p className="mt-1 text-sm text-zinc-500">{s.label}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* CTA */}
        <section className="mx-auto max-w-2xl px-6 py-20 text-center">
          <h2 className="text-2xl font-semibold text-white">Ready to try it?</h2>
          <p className="mt-3 text-sm text-zinc-400">
            No account required. Start with 3 free operations per day, or{" "}
            <Link href="/pricing" className="text-white underline underline-offset-2 hover:no-underline">
              see our plans
            </Link>{" "}
            for more.
          </p>
          <Link
            href="/tools"
            className="mt-8 inline-block rounded-xl bg-white px-6 py-3 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100"
          >
            Browse all tools →
          </Link>
        </section>
      </main>

      <Footer />
    </div>
  )
}
