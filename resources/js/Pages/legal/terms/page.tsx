import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { SEOHead } from "@/components/documate/seo-head"

const sections = [
  {
    title: "1. Acceptance of Terms",
    content: `By accessing or using Documate ("the Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the Service.`,
  },
  {
    title: "2. Description of Service",
    content: `Documate provides online PDF processing tools including merging, compressing, splitting, and converting documents. The Service is available on a free tier with daily operation limits, a Pro subscription plan with unlimited operations, and a pay-per-use credit system.`,
  },
  {
    title: "3. User Accounts",
    content: `You may use basic tools without an account. When you create an account, you are responsible for maintaining the confidentiality of your credentials and for all activity that occurs under your account. You must provide accurate and complete information when registering. You must be at least 16 years old to use this Service.`,
  },
  {
    title: "4. Acceptable Use",
    content: `You agree not to use the Service to: (a) upload files containing illegal, harmful, or infringing content; (b) attempt to circumvent any usage limits or security measures; (c) reverse-engineer, scrape, or automate requests to the Service in an abusive manner; (d) resell or redistribute the Service without written permission.`,
  },
  {
    title: "5. File Privacy & Retention",
    content: `Files you upload are processed solely to perform the requested operation. All files are automatically and permanently deleted from our servers within 24 hours of upload. We do not access, read, or share the contents of your documents. See our Privacy Policy for full details.`,
  },
  {
    title: "6. Subscriptions & Billing",
    content: `Paid subscriptions are billed in advance on a monthly or annual basis via Stripe. Credits are a one-time purchase and do not expire. Subscriptions renew automatically unless cancelled before the renewal date. You may cancel at any time; your access continues until the end of the current billing period. We offer a 30-day money-back guarantee on all paid plans.`,
  },
  {
    title: "7. Refunds",
    content: `We offer a 30-day money-back guarantee on Pro subscriptions. Credit packs are non-refundable once any credits have been used. To request a refund, contact us at support@documate.io within 30 days of your purchase.`,
  },
  {
    title: "8. Intellectual Property",
    content: `You retain all rights to the files you upload and the documents you produce. Documate retains all rights to its software, brand, and user interface. The Service is provided under a limited, non-exclusive, non-transferable licence for personal or business use only.`,
  },
  {
    title: "9. Disclaimers & Limitation of Liability",
    content: `The Service is provided "as is" without warranties of any kind. We do not guarantee that converted or processed files will be error-free. To the maximum extent permitted by law, Documate shall not be liable for any indirect, incidental, or consequential damages arising from your use of the Service.`,
  },
  {
    title: "10. Changes to Terms",
    content: `We may update these Terms at any time. We will notify registered users by email of material changes. Your continued use of the Service after changes are posted constitutes acceptance of the updated Terms.`,
  },
  {
    title: "11. Governing Law",
    content: `These Terms are governed by the laws of the European Union. Any disputes shall be resolved in the courts of the jurisdiction where Documate is registered.`,
  },
  {
    title: "12. Contact",
    content: `For questions about these Terms, contact us at legal@documate.io or through our Contact page.`,
  },
]

export default function TermsPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Terms of Service — Documate"
        description="Read Documate's Terms of Service. Learn about acceptable use, subscriptions, file privacy, and your rights when using our online PDF tools."
        canonical="https://documate.nexkit.app/legal/terms"
      />
      <Navbar />
      <main className="mx-auto max-w-3xl px-6 py-20">
        <div className="mb-12">
          <h1 className="text-3xl font-bold text-white">Terms of Service</h1>
          <p className="mt-2 text-sm text-zinc-500">Last updated: April 2026</p>
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
