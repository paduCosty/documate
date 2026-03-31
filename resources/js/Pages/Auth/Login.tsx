"use client"

import { useState } from "react"
import { Link } from '@inertiajs/react';

import { Eye, EyeOff } from "lucide-react"
import { GuestLayout } from "@/components/documate/guest-layout"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"
import { Checkbox } from "@/components/ui/checkbox"

export default function LoginPage() {
  const [showPassword, setShowPassword] = useState(false)

  return (
    <GuestLayout>
      <div className="w-full max-w-[400px] rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-[0_0_0_1px_#3f3f46,0_16px_48px_rgba(0,0,0,0.6)]">
        <h2 className="text-xl font-semibold text-white">Welcome back</h2>
        <p className="mt-1 text-sm text-zinc-500">Sign in to your account.</p>

        <form className="mt-8 space-y-4">
          <DocumateInput label="Email" type="email" placeholder="you@example.com" />
          
          <div className="relative">
            <DocumateInput
              label="Password"
              type={showPassword ? "text" : "password"}
              placeholder="Enter your password"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-[30px] text-zinc-600 transition-colors hover:text-zinc-400"
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>

          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Checkbox id="remember" className="border-zinc-700 data-[state=checked]:bg-white data-[state=checked]:text-black" />
              <label htmlFor="remember" className="text-sm text-zinc-400">Remember me</label>
            </div>
            <Link href="/forgot-password" className="text-sm text-zinc-500 transition-colors hover:text-white">
              Forgot password?
            </Link>
          </div>

          <DocumateButton className="mt-6 w-full">Sign in</DocumateButton>
        </form>

        {/* Divider */}
        <div className="mt-6 flex items-center gap-3">
          <div className="h-px flex-1 bg-zinc-800" />
          <span className="text-xs text-zinc-600">or</span>
          <div className="h-px flex-1 bg-zinc-800" />
        </div>

        {/* Google Sign In */}
        <button className="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-700 py-2.5 text-sm font-medium text-zinc-300 transition-colors hover:bg-zinc-800">
          <svg className="h-4 w-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
          </svg>
          Continue with Google
        </button>

        {/* Register Link */}
        <p className="mt-8 text-center text-sm text-zinc-500">
          Don&apos;t have an account?{" "}
          <Link href="/register" className="text-white transition-colors hover:underline">
            Create an account &rarr;
          </Link>
        </p>
      </div>
    </GuestLayout>
  )
}
