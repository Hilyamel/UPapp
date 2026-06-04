import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

// PrimeReact CSS
import 'primereact/resources/themes/lara-light-blue/theme.css';
import 'primereact/resources/primereact.min.css';
import 'primeicons/primeicons.css';

// Font Awesome CSS
import '@fortawesome/fontawesome-free/css/all.min.css';

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
