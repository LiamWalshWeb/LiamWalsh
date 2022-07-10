---
layout: layouts/home.njk
title: About Me
templateClass: tmpl-home
eleventyNavigation:
  key: About Me
  order: 3
---

<article class="px-5 md:px-10 lg:px-14 py-9 md:py-14 lg:py-16 bg-gradient-to-b from-phthalo-green to-hunter-green text-white">
  <div class="container">
    <h1 class="text-2xl md:text-3xl lg:text-4xl font-semibold mb-5">Hello there! 👋😄</h1>
    <p class="text-md md:text-lg lg:text-xl mb-4">
      My name is Liam Walsh and I'm a front end web developer from Manchester, UK. I currently work for Awaze as a Software Engineer and I love to build all things web related.
    </p>
    <p class="text-md md:text-lg lg:text-xl mb-4">
      I'm currently revamping this page so check back another time for details 👍
    </p>
    <p class="text-md md:text-lg lg:text-xl">
      If you'd like to get in touch feel free to <a class="text-lemon-yellow" href="mailto:{{ metadata.email }}">email me</a>, <a class="text-lemon-yellow" href="{{ metadata.twitter }}">tweet me</a> or <a class="text-lemon-yellow" href="{{ metadata.linkedin }}">connect with me on LinkedIn</a>.
    </p>
  </div>
</article>
