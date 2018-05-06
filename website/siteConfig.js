const siteConfig = {
  title: 'Mill',
  tagline: 'A small annotation DSL for documenting a REST API.',

  url: 'https://vimeo.github.io',
  baseUrl: '/', // baseUrl: '/mill/',
  editUrl: 'https://github.com/vimeo/mill/edit/master/docs/',

  projectName: 'mill',
  organizationName: 'vimeo',

  headerLinks: [
    {doc: 'writing-documentation', label: 'Docs'},
    {href: 'https://github.com/vimeo/mill', label: 'GitHub'}
  ],

  headerIcon: 'img/mill.svg',
  favicon: 'img/favicon.png',

  colors: {
    primaryColor: '#006272',
    secondaryColor: '#E57200'
  },

  copyright: 'Copyright © ' + new Date().getFullYear() + ' Vimeo',

  onPageNav: 'separate',

  highlight: {
    theme: 'atom-one-light'
  }
}

module.exports = siteConfig
