import { useForm } from '@inertiajs/react'
import { FormEvent, useState } from 'react'
import { Lock, Mail, AlertCircle, CheckCircle, Clock } from 'lucide-react'

interface Props {
    status?: 'request_sent' | 'already_pending' | 'link_resent' | null
    errors?: { password?: string }
}

const statusMessages = {
    request_sent:    { icon: CheckCircle, text: 'Request sent. You\'ll receive an email when access is approved.', color: 'text-emerald-400' },
    already_pending: { icon: Clock,       text: 'Your request is already under review. You\'ll hear from us soon.', color: 'text-yellow-400' },
    link_resent:     { icon: CheckCircle, text: 'Access link resent to your email.',                               color: 'text-emerald-400' },
}

export default function GatePage({ status, errors }: Props) {
    const [tab, setTab] = useState<'password' | 'email'>('password')

    const passwordForm = useForm({ password: '' })
    const emailForm    = useForm({ email: '' })

    function submitPassword(e: FormEvent) {
        e.preventDefault()
        passwordForm.post('/gate/password', { preserveScroll: true })
    }

    function submitEmail(e: FormEvent) {
        e.preventDefault()
        emailForm.post('/gate/request', { preserveScroll: true, onSuccess: () => emailForm.reset() })
    }

    const statusInfo = status ? statusMessages[status] : null

    return (
        <div className="min-h-screen bg-[#0a0a0a] flex flex-col items-center justify-center px-4">
            {/* Logo */}
            <div className="flex items-center gap-2 mb-10">
                <div className="w-8 h-8 bg-white rounded-md flex items-center justify-center">
                    <span className="text-black font-bold text-sm">D</span>
                </div>
                <span className="text-white font-semibold text-lg">documate</span>
            </div>

            {/* Card */}
            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <span className="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-400 border border-neutral-800 rounded-full px-3 py-1 mb-4">
                        <Lock className="w-3 h-3" />
                        Private Beta
                    </span>
                    <h1 className="text-white text-2xl font-semibold">Access required</h1>
                    <p className="text-neutral-500 text-sm mt-2">This app is currently in private beta.</p>
                </div>

                {/* Tabs */}
                <div className="flex rounded-lg border border-neutral-800 p-1 mb-6 bg-neutral-900/50">
                    <button
                        onClick={() => setTab('password')}
                        className={`flex-1 flex items-center justify-center gap-2 text-sm py-2 rounded-md transition-colors ${
                            tab === 'password'
                                ? 'bg-white text-black font-medium'
                                : 'text-neutral-400 hover:text-neutral-200'
                        }`}
                    >
                        <Lock className="w-3.5 h-3.5" />
                        Password
                    </button>
                    <button
                        onClick={() => setTab('email')}
                        className={`flex-1 flex items-center justify-center gap-2 text-sm py-2 rounded-md transition-colors ${
                            tab === 'email'
                                ? 'bg-white text-black font-medium'
                                : 'text-neutral-400 hover:text-neutral-200'
                        }`}
                    >
                        <Mail className="w-3.5 h-3.5" />
                        Request access
                    </button>
                </div>

                {/* Status message (email tab) */}
                {statusInfo && tab === 'email' && (
                    <div className={`flex items-start gap-2 text-sm mb-4 ${statusInfo.color}`}>
                        <statusInfo.icon className="w-4 h-4 mt-0.5 shrink-0" />
                        <span>{statusInfo.text}</span>
                    </div>
                )}

                {/* Password form */}
                {tab === 'password' && (
                    <form onSubmit={submitPassword} className="space-y-3">
                        <div>
                            <input
                                type="password"
                                placeholder="Enter password"
                                value={passwordForm.data.password}
                                onChange={e => passwordForm.setData('password', e.target.value)}
                                className="w-full bg-neutral-900 border border-neutral-800 rounded-lg px-4 py-3 text-white text-sm placeholder-neutral-600 focus:outline-none focus:border-neutral-600 transition-colors"
                                autoFocus
                            />
                            {(passwordForm.errors.password || errors?.password) && (
                                <div className="flex items-center gap-1.5 mt-2 text-red-400 text-xs">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {passwordForm.errors.password || errors?.password}
                                </div>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={passwordForm.processing || !passwordForm.data.password}
                            className="w-full bg-white text-black font-medium text-sm py-3 rounded-lg hover:bg-neutral-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {passwordForm.processing ? 'Checking…' : 'Continue →'}
                        </button>
                    </form>
                )}

                {/* Email form */}
                {tab === 'email' && (
                    <form onSubmit={submitEmail} className="space-y-3">
                        <div>
                            <input
                                type="email"
                                placeholder="you@example.com"
                                value={emailForm.data.email}
                                onChange={e => emailForm.setData('email', e.target.value)}
                                className="w-full bg-neutral-900 border border-neutral-800 rounded-lg px-4 py-3 text-white text-sm placeholder-neutral-600 focus:outline-none focus:border-neutral-600 transition-colors"
                                autoFocus
                            />
                            {emailForm.errors.email && (
                                <div className="flex items-center gap-1.5 mt-2 text-red-400 text-xs">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {emailForm.errors.email}
                                </div>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={emailForm.processing || !emailForm.data.email}
                            className="w-full bg-white text-black font-medium text-sm py-3 rounded-lg hover:bg-neutral-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {emailForm.processing ? 'Sending…' : 'Request access →'}
                        </button>
                        <p className="text-neutral-600 text-xs text-center">
                            You'll receive an email once your request is approved.
                        </p>
                    </form>
                )}
            </div>

            <p className="text-neutral-700 text-xs mt-12">
                © {new Date().getFullYear()} Documate. All rights reserved.
            </p>
        </div>
    )
}
