import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"

const sections = [
  {
    title: "1. Who We Are",
    content: `Documate ("we", "us", "our") operates the documate.io service. This Privacy Policy explains what data we collect, how we use it, and your rights regarding that data.`,
  },
  {
    title: "2. Data We Collect",
    content: `Account users: name, email address, hashed password, and optional social login provider (Google). Payment: billing details are handled entirely by Stripe — we never store card numbers. Usage: daily operation counts and file metadata (filename, size, operation type) to enforce plan limits and display your file history. Guest users: a temporary session identifier stored in a cookie and your IP address for abuse prevention. Files: uploaded documents are temporarily stored only for the duration of processing and deleted within 24 hours.`,
  },
  {
    title: "3. How We Use Your Data",
    content: `We use your data to: (a) provide and improve the Service; (b) enforce plan limits and prevent abuse; (c) send transactional emails (password resets, payment receipts); (d) comply with legal obligations. We do not use your data for advertising or sell it to third parties.`,
  },
  {
    title: "4. File Processing",
    content: `Files you upload are processed automatically by our servers. No human ever reads or inspects your documents. Processed files are stored in temporary encrypted storage and automatically deleted within 24 hours. Output files are available for download for 24 hours, after which they are permanently deleted.`,
  },
  {
    title: "5. Cookies",
    content: `We use essential cookies to maintain your login session and a guest session identifier. We do not use advertising or tracking cookies. You can manage cookies through your browser settings. See our Cookie Policy for full details.`,
  },
  {
    title: "6. Data Sharing",
    content: `We share data only with: Stripe (payment processing), our hosting infrastructure provider (file and database storage in the EU/US). We do not sell, rent, or share your personal data with any third party for marketing purposes.`,
  },
  {
    title: "7. Data Retention",
    content: `Account data is retained for as long as your account is active. You may delete your account at any time, which permanently removes all associated data. File records are retained for display in your file history but the actual files are deleted within 24 hours. Guest session data is deleted after 30 days of inactivity.`,
  },
  {
    title: "8. Your Rights (GDPR)",
    content: `If you are located in the EEA, you have the right to: access the personal data we hold about you; request correction of inaccurate data; request deletion of your data; object to or restrict certain processing; request data portability; withdraw consent at any time. To exercise these rights, contact us at privacy@documate.io.`,
  },
  {
    title: "9. Security",
    content: `All data is transmitted over HTTPS (TLS 1.2+). Passwords are hashed using bcrypt. Files are stored in isolated temporary directories with access restricted to the processing system. We conduct regular security reviews.`,
  },
  {
    title: "10. Changes to This Policy",
    content: `We may update this Privacy Policy periodically. We will notify registered users of material changes by email. The "Last updated" date at the top of this page reflects the most recent revision.`,
  },
  {
    title: "11. Contact",
    content: `For privacy-related questions or to exercise your rights, contact us at privacy@documate.io or through our Contact page.`,
  },
]

export default function PrivacyPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />
      <main className="mx-auto max-w-3xl px-6 py-20">
        <div className="mb-12">
          <h1 className="text-3xl font-bold text-white">Privacy Policy</h1>
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
