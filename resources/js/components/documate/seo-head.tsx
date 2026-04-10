import { Head } from '@inertiajs/react'

interface SEOHeadProps {
  title: string
  description: string
  canonical?: string // full URL, e.g. "https://documate.nexkit.app/tools/merge-pdf"
}

export function SEOHead({ title, description, canonical }: SEOHeadProps) {
  return (
    <Head>
      <title>{title}</title>
      <meta head-key="description" name="description" content={description} />
      <meta head-key="og:title" property="og:title" content={title} />
      <meta head-key="og:description" property="og:description" content={description} />
      <meta head-key="og:type" property="og:type" content="website" />
      <meta head-key="og:site_name" property="og:site_name" content="Documate" />
      {canonical && <meta head-key="og:url" property="og:url" content={canonical} />}
      {canonical && <link head-key="canonical" rel="canonical" href={canonical} />}
      <meta head-key="twitter:card" name="twitter:card" content="summary" />
      <meta head-key="twitter:title" name="twitter:title" content={title} />
      <meta head-key="twitter:description" name="twitter:description" content={description} />
    </Head>
  )
}
