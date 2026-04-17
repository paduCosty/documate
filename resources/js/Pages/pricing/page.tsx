"use client"

import { useState } from "react"
import { Check, ShieldCheck, Zap, Star, Rocket } from "lucide-react"
import { Link, router, usePage } from "@inertiajs/react"
import { SEOHead } from "@/components/documate/seo-head"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateButton } from "@/components/documate/documate-button"
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

interface CreditPack {
  id: string
  name: string
  credits: number
  price_cents: number
  price_label: string
  per_credit: string
  badge: string | null
}

interface Props {
  plans: Plan[]
  creditPacks: Record<string, CreditPack>
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
    question: "How do credits work?",
    answer: "Credits are a pay-per-use option. Each PDF operation (merge, compress, convert, etc.) costs 1 credit. Credits never expire and are great if you only need tools occasionally.",
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

const CREDIT_ICONS: Record<string, typeof Zap> = {
  starter: Zap,
  value: Star,
  power: Rocket,
}

export default function PricingPage({ plans, creditPacks }: Props) {
  const { flash } = usePage<{ props: { flash: { success?: string; error?: string; info?: string } } }>().props as any
  const [billing, setBilling]     = useState<"monthly" | "yearly">("monthly")
  const [upgrading, setUpgrading] = useState(false)
  const [buyingPack, setBuyingPack] = useState<string | null>(null)

  const proPlan     = plans.find((p) => p.id === "pro")
  const monthlyPrice = proPlan?.price_monthly ?? 9
  const yearlyPrice  = proPlan?.price_yearly  ?? 84

  const handleUpgrade = () => {
    setUpgrading(true)
    router.post(route("subscription.checkout", { plan: billing === "yearly" ? "pro_yearly" : "pro_monthly" }))
  }

  const handleBuyCredits = (packId: string) => {
    setBuyingPack(packId)
    router.post(route("credits.checkout", { pack: packId }), {}, {
      onFinish: () => setBuyingPack(null),
    })
  }

  const packs = Object.values(creditPacks)

  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Pricing — Documate"
        description="Free and Pro plans for Documate PDF tools. Start free with 3 operations per day. Upgrade for unlimited access and larger file sizes."
        canonical="/pricing"
      />
      <Navbar />

      <main className="px-6 py-24">
        <div className="mx-auto max-w-4xl">

          {/* ── Flash messages ───────────────────────────────────────────── */}
          {flash?.info && (
            <div className="mb-8 rounded-xl border border-amber-700/50 bg-amber-950/30 px-4 py-3 text-sm text-amber-300">
              {flash.info}
            </div>
          )}
          {flash?.success && (
            <div className="mb-8 rounded-xl border border-emerald-700/50 bg-emerald-950/30 px-4 py-3 text-sm text-emerald-300">
              {flash.success}
            </div>
          )}
          {flash?.error && (
            <div className="mb-8 rounded-xl border border-red-700/50 bg-red-950/30 px-4 py-3 text-sm text-red-300">
              {flash.error}
            </div>
          )}

          {/* ── Header ────────────────────────────────────────────────────── */}
          <div className="text-center">
            <h1 className="text-4xl font-bold tracking-tight text-white md:text-5xl">
              Simple, transparent pricing
            </h1>
            <p className="mt-4 text-zinc-500">
              Start free. Subscribe for unlimited access. Or buy credits and pay only for what you use.
            </p>
          </div>

          {/* ── Subscription toggle ───────────────────────────────────────── */}
          <div className="mt-10 flex justify-center">
            <BillingToggle
              value={billing}
              onChange={setBilling}
              savingsPct={Math.round((monthlyPrice * 12 - yearlyPrice) / (monthlyPrice * 12) * 100)}
            />
          </div>

          {/* ── Subscription plan cards ───────────────────────────────────── */}
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

          {/* ── Credit packs ──────────────────────────────────────────────── */}
          {packs.length > 0 && (
            <div className="mt-20">
              <div className="text-center">
                <h2 className="text-2xl font-semibold text-white">Pay as you go — Credits</h2>
                <p className="mt-2 text-sm text-zinc-500">
                  No subscription needed. Each PDF operation costs 1 credit. Credits never expire.
                </p>
              </div>

              <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                {packs.map((pack) => {
                  const Icon = CREDIT_ICONS[pack.id] ?? Zap
                  const isPopular = pack.badge === "Popular"
                  const isBest    = pack.badge === "Best value"

                  return (
                    <div
                      key={pack.id}
                      className={`relative overflow-hidden rounded-2xl border bg-zinc-900 p-6 flex flex-col ${
                        isPopular ? "border-white/30" : "border-zinc-800"
                      }`}
                    >
                      {pack.badge && (
                        <div className="absolute right-3 top-3">
                          <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                            isPopular ? "bg-white text-black" : "bg-zinc-700 text-zinc-300"
                          }`}>
                            {pack.badge}
                          </span>
                        </div>
                      )}

                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-800">
                          <Icon className="h-5 w-5 text-zinc-400" />
                        </div>
                        <div>
                          <p className="text-sm font-medium text-white">{pack.name}</p>
                          <p className="text-xs text-zinc-500">{pack.credits} credits</p>
                        </div>
                      </div>

                      <div className="mt-5 flex items-end gap-1">
                        <span className="text-3xl font-bold text-white">{pack.price_label}</span>
                        <span className="mb-0.5 text-sm text-zinc-500">{pack.per_credit} each</span>
                      </div>

                      <ul className="mt-4 space-y-2 flex-1">
                        <li className="flex items-center gap-2 text-sm text-zinc-400">
                          <Check className="h-3.5 w-3.5 shrink-0 text-zinc-600" />
                          {pack.credits} PDF operations
                        </li>
                        <li className="flex items-center gap-2 text-sm text-zinc-400">
                          <Check className="h-3.5 w-3.5 shrink-0 text-zinc-600" />
                          Up to 25MB per file
                        </li>
                        <li className="flex items-center gap-2 text-sm text-zinc-400">
                          <Check className="h-3.5 w-3.5 shrink-0 text-zinc-600" />
                          Credits never expire
                        </li>
                      </ul>

                      <DocumateButton
                        className="mt-6 w-full"
                        variant={isPopular ? "default" : "outline"}
                        onClick={() => handleBuyCredits(pack.id)}
                        disabled={buyingPack !== null}
                      >
                        {buyingPack === pack.id ? "Redirecting…" : `Buy ${pack.credits} credits`}
                      </DocumateButton>
                    </div>
                  )
                })}
              </div>
            </div>
          )}

          {/* ── FAQ ───────────────────────────────────────────────────────── */}
          <div className="mx-auto mt-24 max-w-2xl">
            <h2 className="text-center text-2xl font-semibold text-white">
              Frequently asked questions
            </h2>
            <Accordion type="single" collapsible className="mt-8 space-y-2">
              {faqs.map((faq, i) => (
                <AccordionItem
                  key={i}
                  value={`faq-${i}`}
                  className="rounded-xl border border-zinc-800 bg-zinc-900 px-5"
                >
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

          {/* ── Money-back banner ─────────────────────────────────────────── */}
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
