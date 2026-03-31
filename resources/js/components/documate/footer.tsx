import { Link } from '@inertiajs/react';

import { Logo } from "./logo"

const toolLinks = [
  { name: "Merge PDF", href: "/tools/merge-pdf" },
  { name: "Compress PDF", href: "/tools/compress-pdf" },
  { name: "Word to PDF", href: "/tools/word-to-pdf" },
  { name: "Excel to PDF", href: "/tools/excel-to-pdf" },
  { name: "PPT to PDF", href: "/tools/ppt-to-pdf" },
  { name: "Split PDF", href: "/tools/split-pdf" },
  { name: "PDF to JPG", href: "/tools/pdf-to-jpg" },
]

const companyLinks = [
  { name: "About", href: "/about" },
  { name: "Blog", href: "/blog" },
  { name: "Contact", href: "/contact" },
]

const legalLinks = [
  { name: "Terms", href: "/legal/terms" },
  { name: "Privacy", href: "/legal/privacy" },
  { name: "Cookie Policy", href: "/legal/cookies" },
  { name: "Refund", href: "/legal/refund" },
]

export function Footer() {
  return (
    <footer className="border-t border-zinc-800 bg-zinc-950 py-16">
      <div className="mx-auto max-w-7xl px-6">
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
          {/* Brand Column */}
          <div className="col-span-2 md:col-span-1">
            <Logo />
            <p className="mt-4 text-sm text-zinc-500">PDF tools that just work.</p>
            <div className="mt-4 inline-flex items-center gap-2 rounded-full bg-zinc-800 px-3 py-1.5 text-xs text-zinc-400">
              <span className="h-1.5 w-1.5 rounded-full bg-green-500" />
              All files encrypted and deleted after 24h
            </div>
          </div>

          {/* Tools Column */}
          <div>
            <h3 className="text-sm font-medium text-white">Tools</h3>
            <ul className="mt-4 space-y-3">
              {toolLinks.map((link) => (
                <li key={link.name}>
                  <Link href={link.href} className="text-sm text-zinc-500 transition-colors hover:text-white">
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h3 className="text-sm font-medium text-white">Company</h3>
            <ul className="mt-4 space-y-3">
              {companyLinks.map((link) => (
                <li key={link.name}>
                  <Link href={link.href} className="text-sm text-zinc-500 transition-colors hover:text-white">
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h3 className="text-sm font-medium text-white">Legal</h3>
            <ul className="mt-4 space-y-3">
              {legalLinks.map((link) => (
                <li key={link.name}>
                  <Link href={link.href} className="text-sm text-zinc-500 transition-colors hover:text-white">
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-16 flex flex-col items-center justify-between gap-4 border-t border-zinc-800 pt-8 md:flex-row">
          <p className="text-sm text-zinc-600">&copy; 2025 Documate. All rights reserved.</p>
          <p className="text-sm text-zinc-600">Built for speed. Secured by design.</p>
        </div>
      </div>
    </footer>
  )
}
