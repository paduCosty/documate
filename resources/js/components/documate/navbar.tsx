"use client"

import { useState } from "react"
import { Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';

import { ChevronDown, Menu, X, GitMerge, Minimize2, FileText, Table, Presentation, Scissors, Image } from "lucide-react"
import { Logo } from "./logo"
import { cn } from "@/lib/utils"

const tools = [
  { name: "Merge PDF", description: "Combine multiple PDFs into one", icon: GitMerge, href: route('tools.merge-pdf') },
  { name: "Compress PDF", description: "Reduce PDF file size", icon: Minimize2, href: route('tools.compress-pdf') },
  { name: "Word to PDF", description: "Convert .doc/.docx to PDF", icon: FileText, href: route('tools.word-to-pdf') },
  { name: "Excel to PDF", description: "Convert spreadsheets to PDF", icon: Table, href: route('tools.excel-to-pdf') },
  { name: "PPT to PDF", description: "Convert presentations to PDF", icon: Presentation, href: route('tools.ppt-to-pdf') },
  { name: "Split PDF", description: "Extract pages from PDF", icon: Scissors, href: route('tools.split-pdf') },
  { name: "PDF to JPG", description: "Convert PDF to images", icon: Image, href: route('tools.pdf-to-jpg') },
]

export function Navbar() {
  const [isToolsOpen, setIsToolsOpen] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  const { props } = usePage<{ auth?: { user?: { name: string; email: string; plan: "free" | "pro" | "business" } } }>()
  const user = props?.auth?.user

  return (
    <nav className="sticky top-0 z-50 border-b border-zinc-800 bg-[#09090b]/80 backdrop-blur-xl">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
        <Logo />

        {/* Desktop Navigation */}
        <div className="hidden items-center gap-8 md:flex">
          <div 
            className="relative"
            onMouseEnter={() => setIsToolsOpen(true)}
            onMouseLeave={() => setIsToolsOpen(false)}
          >
            <button className="flex items-center gap-1 text-sm text-zinc-400 transition-colors duration-150 hover:text-white">
              Tools
              <ChevronDown className={cn("h-4 w-4 transition-transform duration-150", isToolsOpen && "rotate-180")} />
            </button>

            {/* Tools Dropdown */}
            {isToolsOpen && (
              <div className="absolute left-1/2 top-full pt-2 -translate-x-1/2">
                <div className="w-[480px] rounded-2xl border border-zinc-800 bg-zinc-900 p-4 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
                  <div className="grid grid-cols-2 gap-1">
                    {tools.map((tool) => (
                      <Link
                        key={tool.name}
                        href={tool.href}
                        className="flex items-start gap-3 rounded-xl p-3 transition-colors hover:bg-zinc-800"
                      >
                        <tool.icon className="mt-0.5 h-[18px] w-[18px] text-zinc-500" />
                        <div>
                          <div className="text-sm font-medium text-white">{tool.name}</div>
                          <div className="text-xs text-zinc-500">{tool.description}</div>
                        </div>
                      </Link>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          <Link href="/pricing" className="text-sm text-zinc-400 transition-colors duration-150 hover:text-white">
            Pricing
          </Link>
          <Link href="/faq" className="text-sm text-zinc-400 transition-colors duration-150 hover:text-white">
            FAQ
          </Link>
        </div>

        {/* Desktop CTA */}
        <div className="hidden items-center gap-4 md:flex">
          {user ? (
            <Link
              href="/dashboard"
              className="rounded-xl bg-white px-4 py-2 text-sm font-medium text-black shadow-[0_0_0_1px_rgba(255,255,255,0.08),0_2px_8px_rgba(0,0,0,0.3)] transition-colors hover:bg-zinc-100"
            >
              Dashboard
            </Link>
          ) : (
            <>
              <Link href="/login" className="text-sm text-zinc-400 transition-colors duration-150 hover:text-white">
                Log in
              </Link>
              <Link
                href="/register"
                className="rounded-xl bg-white px-4 py-2 text-sm font-medium text-black shadow-[0_0_0_1px_rgba(255,255,255,0.08),0_2px_8px_rgba(0,0,0,0.3)] transition-colors hover:bg-zinc-100"
              >
                Get started
              </Link>
            </>
          )}
        </div>

        {/* Mobile Menu Button */}
        <button
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          className="p-2 text-zinc-400 hover:text-white md:hidden"
        >
          {isMobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {/* Mobile Menu */}
      {isMobileMenuOpen && (
        <div className="border-t border-zinc-800 bg-zinc-950 px-6 py-4 md:hidden">
          <div className="space-y-4">
            <div className="space-y-2">
              <div className="text-xs font-medium uppercase tracking-widest text-zinc-600">Tools</div>
              {tools.map((tool) => (
                <Link
                  key={tool.name}
                  href={tool.href}
                  className="flex items-center gap-3 rounded-xl p-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  <tool.icon className="h-4 w-4" />
                  {tool.name}
                </Link>
              ))}
            </div>
            <div className="space-y-2 border-t border-zinc-800 pt-4">
              <Link
                href="/pricing"
                className="block rounded-xl p-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Pricing
              </Link>
              <Link
                href="/faq"
                className="block rounded-xl p-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                FAQ
              </Link>
            </div>
            <div className="space-y-2 border-t border-zinc-800 pt-4">
              {user ? (
                <Link
                  href="/dashboard"
                  className="block rounded-xl bg-white p-2 text-center text-sm font-medium text-black transition-colors hover:bg-zinc-100"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Dashboard
                </Link>
              ) : (
                <>
                  <Link
                    href="/login"
                    className="block rounded-xl p-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    onClick={() => setIsMobileMenuOpen(false)}
                  >
                    Log in
                  </Link>
                  <Link
                    href="/register"
                    className="block rounded-xl bg-white p-2 text-center text-sm font-medium text-black transition-colors hover:bg-zinc-100"
                    onClick={() => setIsMobileMenuOpen(false)}
                  >
                    Get started
                  </Link>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </nav>
  )
}
