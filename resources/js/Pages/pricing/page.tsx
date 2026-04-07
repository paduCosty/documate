"use client"

import { useState } from "react"
import { Check, ShieldCheck } from "lucide-react"
import { Link, router } from "@inertiajs/react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { BillingToggle, ProPlanCard } from "@/components/documate/plan-card"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"

interface Plan {
  id: string
  name: string
  price_monthly: number
  price_yearly: number
  features: string[]
  popular?: boolean
}

interface Props {
  plans: Plan[]
}

const faqs = [
  {
    question: "Can I cancel anytime?",
    answer: "Yes, you can cancel your subscription at any time. Your access will continue until the end of your billing period.",
  },
  {
    question: "What payment methods do you accept?",
    answer: "We accept all major credit cards (Visa, MasterCard, American Express) and SEPA. All payments are processed securely through Stripe.",
  },
  {
    question: "Is there a free trial?",
    answer: "We don't offer a traditional free trial, but our Free plan gives you 3 operations per day forever. You can upgrade anytime.",
  },
  {
    question: "Can I switch plans?",
    answer: "Yes, you can upgrade or downgrade your plan at any time. Prorated charges apply when upgrading.",
  },
  {
    question: "Do you offer refunds?",
    answer: "Yes, we offer a 30-day money-back guarantee on all paid plans.",
  },
]

const FREE_FEATURES = [
  "3 operations/day",
  "10MB max file size",
  "Merge & Compress PDF",
  "Word/Excel/PPT to PDF",
  "No account required",
]

export default function PricingPage({ plans }: Props) {
  const [billing, setBilling] = useState<"monthly" | "yearly">("monthly")
  const [upgrading, setUpgrading] = useState(false)

  const proPlan = plans.find((p) => p.id === "pro")
  const monthlyPrice = proPlan?.price_monthly ?? 9
  const yearlyPrice  = proPlan?.price_yearly  ?? 84

  const handleUpgrade = () => {
    setUpgrading(true)
    router.post(route("subscription.checkout", { plan: billing === "yearly" ? "pro_yearly" : "pro_monthly" }))
  }

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="px-6 py-24">
        <div className="mx-auto max-w-4xl">
          {/* Header */}
          <div className="text-center">
            <h1 className="text-4xl font-bold tracking-tight text-white md:text-5xl">
              Simple, transparent pricing
            </h1>
            <p className="mt-4 text-zinc-500">
              Start free. Upgrade when you need more. No hidden fees.
            </p>
          </div>

          {/* Toggle */}
          <div className="mt-10 flex justify-center">
            <BillingToggle value={billing} onChange={setBilling} savingsPct={Math.round((monthlyPrice * 12 - yearlyPrice) / (monthlyPrice * 12) * 100)} />
          </div>

          {/* Plan cards */}
          <div className="mt-12 grid grid-cols-1 gap-4 md:grid-cols-2 md:items-start">
            {/* Free */}
            <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
              <div className="border-b border-zinc-800 bg-zinc-800/20 px-6 py-5">
                <span className="text-sm font-medium text-zinc-400">Free</span>
                <div className="mt-3 flex items-end gap-1">
                  <span className="text-4xl font-bold text-white">€0</span>
                  <span className="mb-1 text-sm text-zinc-500">/month</span>
                </div>
                <p className="mt-1 text-xs text-zinc-600">Forever free, no credit card needed</p>
              </div>

              <div className="px-6 py-5">
                <ul className="space-y-3">
                  {FREE_FEATURES.map((f) => (
                    <li key={f} className="flex items-center gap-3 text-sm text-zinc-400">
                      <Check className="h-4 w-4 shrink-0 text-zinc-600" />
                      {f}
                    </li>
                  ))}
                </ul>
              </div>

              <div className="border-t border-zinc-800 px-6 pb-6 pt-5">
                <Link href="/register">
                  <button className="w-full rounded-xl border border-zinc-700 bg-transparent py-3 text-sm font-semibold text-white transition-colors hover:bg-zinc-800">
                    Get started free
                  </button>
                </Link>
              </div>
            </div>

            {/* Pro */}
            <ProPlanCard
              billing={billing}
              monthlyPrice={monthlyPrice}
              yearlyPrice={yearlyPrice}
              onUpgrade={handleUpgrade}
              loading={upgrading}
              variant="page"
            />
          </div>

          {/* FAQ */}
          <div className="mx-auto mt-24 max-w-2xl">
            <h2 className="text-center text-2xl font-semibold text-white">
              Frequently asked questions
            </h2>
            <Accordion type="single" collapsible className="mt-8 space-y-2">
              {faqs.map((faq, i) => (
                <AccordionItem
                  key={i}
                  value={`faq-${i}`}
                  className="rounded-xl border border-zinc-800 bg-zinc-900 px-5">
                  <AccordionTrigger className="py-4 text-left text-sm font-medium text-white">
                    {faq.question}
                  </AccordionTrigger>
                  <AccordionContent className="pb-4 text-sm leading-6 text-zinc-400">
                    {faq.answer}
                  </AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          </div>

          {/* Money-back banner */}
          <DocumateCard className="mt-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-950">
              <ShieldCheck className="h-6 w-6 text-emerald-400" />
            </div>
            <h3 className="mt-4 text-lg font-semibold text-white">30-day money-back guarantee</h3>
            <p className="mt-2 text-sm text-zinc-500">
              Not satisfied? Get a full refund within 30 days, no questions asked.
            </p>
          </DocumateCard>
        </div>
      </main>

      <Footer />
    </div>
  )
}
