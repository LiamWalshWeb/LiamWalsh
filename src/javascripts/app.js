// import './modules'

// Let's register our serviceworker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
}
