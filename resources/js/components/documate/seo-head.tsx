import { Head } from '@inertiajs/react'

const OG_IMAGE = 'https://documate.nexkit.app/og-image.png'

interface SEOHeadProps {
  title: string
  description: string
  canonical?: string
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
      <meta head-key="og:image" property="og:image" content={OG_IMAGE} />
      <meta head-key="og:image:width" property="og:image:width" content="1200" />
      <meta head-key="og:image:height" property="og:image:height" content="630" />
      {canonical && <meta head-key="og:url" property="og:url" content={canonical} />}
      {canonical && <link head-key="canonical" rel="canonical" href={canonical} />}
      <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
      <meta head-key="twitter:title" name="twitter:title" content={title} />
      <meta head-key="twitter:description" name="twitter:description" content={description} />
      <meta head-key="twitter:image" name="twitter:image" content={OG_IMAGE} />
    </Head>
  )
}
