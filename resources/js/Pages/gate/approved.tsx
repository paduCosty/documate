import { CheckCircle } from 'lucide-react'

interface Props {
    email: string
}

export default function GateApproved({ email }: Props) {
    return (
        <div className="min-h-screen bg-[#0a0a0a] flex flex-col items-center justify-center px-4">
            <div className="flex items-center gap-2 mb-10">
                <div className="w-8 h-8 bg-white rounded-md flex items-center justify-center">
                    <span className="text-black font-bold text-sm">D</span>
                </div>
                <span className="text-white font-semibold text-lg">documate</span>
            </div>

            <div className="text-center max-w-sm">
                <CheckCircle className="w-12 h-12 text-emerald-400 mx-auto mb-4" />
                <h1 className="text-white text-2xl font-semibold mb-2">Access granted</h1>
                <p className="text-neutral-400 text-sm">
                    An access link has been sent to <span className="text-white">{email}</span>.
                </p>
            </div>
        </div>
    )
}
