const React = require('react')
const CompLibrary = require('../../core/CompLibrary.js')
const Container = CompLibrary.Container
const GridBlock = CompLibrary.GridBlock

const siteConfig = require(process.cwd() + '/siteConfig.js')

function imgUrl (img) {
  return siteConfig.baseUrl + 'img/' + img
}

function docUrl (doc, language) {
  return siteConfig.baseUrl + 'docs/' + (language ? language + '/' : '') + doc
}

class Button extends React.Component {
  render () {
    return (
      <div className='pluginWrapper buttonWrapper'>
        <a className='button' href={this.props.href} target='_self'>
          {this.props.children}
        </a>
      </div>
    )
  }
}

class HomeSplash extends React.Component {
  render () {
    let language = this.props.language || ''
    return (
      <div className='homeContainer'>
        <div className='homeSplashFade'>
          <div className='wrapper homeWrapper'>
            <div className='inner'>
              <h2 className='projectTitle'>
                ☴ {siteConfig.title}
                <small>{siteConfig.tagline}</small>
              </h2>

              <div className='section promoSection'>
                <div className='promoRow'>
                  <div className='pluginRowBlock'>
                    <Button href={docUrl('writing-documentation.html', language)}>Check It Out</Button>
                    <Button href='https://github.com/vimeo/mill'>GitHub</Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    )
  }
}

const Block = props => (
  <Container
    padding={['bottom', 'top']}
    id={props.id}
    background={props.background}>
    <GridBlock align='center' contents={props.children} layout={props.layout} />
  </Container>
)

const Features = props => (
  <Block layout='twoColumn'>
    {[
      {
        content: 'Generate <a href="https://apiblueprint.org/">API Blueprint</a> specifications from your documentation.',
        image: imgUrl('api_blueprint.png'),
        imageAlign: 'top',
        title: 'API Blueprint'
      },
      {
        content: 'Automatically compiled, <a href="https://keepachangelog.com/en/1.0.0/">Keep a Changelog</a>-friendly, changelogs of your documentation.',
        image: imgUrl('keep-a-changelog.svg'),
        imageAlign: 'top',
        title: 'Changelogs'
      }
    ]}
  </Block>
)

class Index extends React.Component {
  render () {
    let language = this.props.language || ''

    return (
      <div>
        <HomeSplash language={language} />
        <div className='mainContainer'>
          <Features />
        </div>
      </div>
    )
  }
}

module.exports = Index
