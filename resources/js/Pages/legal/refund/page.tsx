import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { Link } from "@inertiajs/react"

const sections = [
  {
    title: "30-Day Money-Back Guarantee",
    content: `We offer a full 30-day money-back guarantee on all new Pro subscriptions (monthly and annual). If you are not satisfied with Documate Pro for any reason, contact us within 30 days of your initial purchase and we will issue a full refund — no questions asked.`,
  },
  {
    title: "Subscription Renewals",
    content: `The 30-day guarantee applies only to the first payment for a new subscription. Renewal charges are non-refundable. If you wish to stop being charged, please cancel your subscription before the next renewal date. You can cancel at any time from Dashboard → Billing.`,
  },
  {
    title: "Credit Packs",
    content: `Credit packs are non-refundable once any credits from the pack have been used. If you purchased a credit pack but have not used any credits, contact us within 14 days of purchase for a full refund.`,
  },
  {
    title: "Partially Used Periods",
    content: `When you cancel a Pro subscription, you retain access until the end of your current billing period. We do not issue prorated refunds for unused time within a billing period, except in the case of the 30-day guarantee on a first-time purchase.`,
  },
  {
    title: "How to Request a Refund",
    content: `Email us at support@documate.io with the subject "Refund Request" and include the email address associated with your account. We aim to process all refund requests within 3 business days. Refunds are issued to the original payment method.`,
  },
  {
    title: "Exceptions",
    content: `We reserve the right to deny refund requests where there is evidence of abuse, such as repeated purchases and refund requests or violation of our Terms of Service.`,
  },
]

export default function RefundPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />
      <main className="mx-auto max-w-3xl px-6 py-20">
        <div className="mb-12">
          <h1 className="text-3xl font-bold text-white">Refund Policy</h1>
          <p className="mt-2 text-sm text-zinc-500">Last updated: April 2026</p>
        </div>

        {/* Highlight box */}
        <div className="mb-10 rounded-xl border border-emerald-800/50 bg-emerald-950/20 px-6 py-5">
          <p className="text-sm font-medium text-emerald-300">30-Day Money-Back Guarantee</p>
          <p className="mt-1 text-sm text-zinc-400">
            Not happy with Pro? We'll refund you in full within 30 days — no questions asked.
            {" "}<Link href="/contact" className="text-white underline underline-offset-2 hover:no-underline">Contact us</Link> to request a refund.
          </p>
        </div>

        <div className="space-y-10">
          {sections.map((s) => (
            <section key={s.title}>
              <h2 className="text-base font-semibold text-white">{s.title}</h2>
              <p className="mt-2 text-sm leading-7 text-zinc-400">{s.content}</p>
            </section>
          ))}
        </div>
      </main>
      <Footer />
    </div>
  )
}
