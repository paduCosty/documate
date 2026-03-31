"use client"

import { useState } from "react"
import { Link } from "@inertiajs/react"
import { Check, ShieldCheck } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"

const faqs = [
  { question: "Can I cancel anytime?", answer: "Yes, you can cancel your subscription at any time. Your access will continue until the end of your billing period." },
  { question: "What payment methods do you accept?", answer: "We accept all major credit cards (Visa, MasterCard, American Express) and PayPal. All payments are processed securely through Stripe." },
  { question: "Is there a free trial?", answer: "We don't offer a traditional free trial, but our Free plan gives you 3 operations per day forever. You can upgrade anytime when you need more." },
  { question: "Can I switch plans?", answer: "Yes, you can upgrade or downgrade your plan at any time. When upgrading, you'll be charged the prorated difference. When downgrading, your new rate applies at the next billing cycle." },
  { question: "Do you offer refunds?", answer: "Yes, we offer a 30-day money-back guarantee on all paid plans. If you're not satisfied, contact us for a full refund within 30 days of your purchase." },
]

const freePlanFeatures = [
  "3 operations/day",
  "10MB max file size",
  "Merge & Compress PDF",
  "Word/Excel/PPT to PDF",
  "No account required",
]

const proPlanFeatures = [
  "Unlimited operations",
  "100MB max file size",
  "All 7 tools",
  "Sign PDF + OCR",
  "30-day file history",
  "Priority processing",
]

const businessPlanFeatures = [
  "Everything in Pro",
  "500MB max file size",
  "AI: Chat with PDF",
  "AI: Translate PDF",
  "Public API access",
  "1-year file history",
  "Invoice download",
  "Dedicated support",
]

export default function PricingPage() {
  const [isYearly, setIsYearly] = useState(false)

  const proPrice = isYearly ? "€5.60" : "€7"
  const businessPrice = isYearly ? "€15.20" : "€19"

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="px-6 py-24">
        <div className="mx-auto max-w-5xl text-center">
          <h1 className="text-4xl font-bold tracking-tight text-white md:text-5xl">Simple pricing</h1>
          <p className="mt-4 text-zinc-500">Start free. No credit card required.</p>

          {/* Toggle */}
          <div className="mt-10 flex items-center justify-center gap-2">
            <div className="inline-flex rounded-full bg-zinc-800 p-1">
              <button
                onClick={() => setIsYearly(false)}
                className={`rounded-full px-4 py-1.5 text-sm transition-all ${
                  !isYearly ? "bg-zinc-600 text-white" : "text-zinc-500"
                }`}
              >
                Monthly
              </button>
              <button
                onClick={() => setIsYearly(true)}
                className={`rounded-full px-4 py-1.5 text-sm transition-all ${
                  isYearly ? "bg-zinc-600 text-white" : "text-zinc-500"
                }`}
              >
                Yearly
              </button>
            </div>
            {isYearly && <DocumateBadge variant="success">Save 20%</DocumateBadge>}
          </div>

          {/* Pricing Cards */}
          <div className="mt-16 grid grid-cols-1 gap-4 md:grid-cols-3">
            {/* Free Plan */}
            <DocumateCard className="text-left">
              <span className="text-sm font-medium text-zinc-400">Free</span>
              <div className="mt-2">
                <span className="text-5xl font-bold text-white">&euro;0</span>
                <span className="ml-1 text-base text-zinc-500">/month</span>
              </div>
              <div className="my-6 border-t border-zinc-800" />
              <ul className="space-y-3">
                {freePlanFeatures.map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 flex-shrink-0 text-zinc-600" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register" className="mt-8 block">
                <DocumateButton variant="outline" className="w-full">Get started free</DocumateButton>
              </Link>
            </DocumateCard>

            {/* Pro Plan */}
            <div className="relative rounded-2xl border-2 border-white bg-zinc-900 p-6 text-left shadow-[0_0_60px_rgba(255,255,255,0.04)]">
              <DocumateBadge variant="pro" className="absolute right-4 top-4">Most Popular</DocumateBadge>
              <span className="text-sm font-medium text-zinc-400">Pro</span>
              <div className="mt-2">
                <span className="text-5xl font-bold text-white">{proPrice}</span>
                <span className="ml-1 text-base text-zinc-500">/month</span>
              </div>
              <div className="my-6 border-t border-zinc-800" />
              <ul className="space-y-3">
                {proPlanFeatures.map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 flex-shrink-0 text-[#22c55e]" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register?plan=pro" className="mt-8 block">
                <DocumateButton className="w-full">Get Pro &rarr;</DocumateButton>
              </Link>
            </div>

            {/* Business Plan */}
            <DocumateCard className="text-left">
              <span className="text-sm font-medium text-zinc-400">Business</span>
              <div className="mt-2">
                <span className="text-5xl font-bold text-white">{businessPrice}</span>
                <span className="ml-1 text-base text-zinc-500">/month</span>
              </div>
              <div className="my-6 border-t border-zinc-800" />
              <ul className="space-y-3">
                {businessPlanFeatures.map((feature) => (
                  <li key={feature} className="flex items-center gap-2 text-sm text-zinc-400">
                    <Check className="h-3.5 w-3.5 flex-shrink-0 text-[#22c55e]" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Link href="/register?plan=business" className="mt-8 block">
                <DocumateButton variant="outline" className="w-full">Get Business &rarr;</DocumateButton>
              </Link>
            </DocumateCard>
          </div>

          {/* FAQ Section */}
          <div className="mx-auto mt-24 max-w-2xl">
            <h2 className="text-2xl font-semibold text-white">Frequently asked questions</h2>
            <Accordion type="single" collapsible className="mt-8 space-y-2">
              {faqs.map((faq, index) => (
                <AccordionItem
                  key={index}
                  value={`faq-${index}`}
                  className="rounded-xl border border-zinc-800 bg-zinc-900 px-5"
                >
                  <AccordionTrigger className="py-4 text-left text-sm font-medium text-white hover:no-underline">
                    {faq.question}
                  </AccordionTrigger>
                  <AccordionContent className="pb-4 text-sm leading-6 text-zinc-400">
                    {faq.answer}
                  </AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          </div>

          {/* Money-back Banner */}
          <DocumateCard className="mt-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[rgba(34,197,94,0.10)]">
              <ShieldCheck className="h-6 w-6 text-[#22c55e]" />
            </div>
            <h3 className="mt-4 text-lg font-semibold text-white">30-day money-back guarantee</h3>
            <p className="mt-2 text-sm text-zinc-500">Not satisfied? Get a full refund within 30 days, no questions asked.</p>
          </DocumateCard>
        </div>
      </main>

      <Footer />
    </div>
  )
}
