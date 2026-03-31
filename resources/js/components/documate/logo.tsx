"use client"

import { Link } from '@inertiajs/react';

import { cn } from "@/lib/utils"

interface LogoProps {
  className?: string
  iconOnly?: boolean
}

export function Logo({ className, iconOnly = false }: LogoProps) {
  return (
    <Link href="/" className={cn("flex items-center gap-2", className)}>
      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-white">
        <span className="text-lg font-bold text-zinc-950">D</span>
      </div>
      {!iconOnly && (
        <span className="text-lg font-semibold text-white">documate</span>
      )}
    </Link>
  )
}
