// Header mit Hauptnavigation auf Basis von EtchMegaMenuPro (EMMP).
// Die EMMP-Komponenten sind in Etch hinterlegt; eingebunden werden sie per WordPress-ID.
// Hüllen-Elemente und Style-IDs stammen aus dem EMMP-Beispiel-Header, damit das EMMP-Styling greift.

import { el, t, text, emmp, gruppe, club, telHref, icon, svgEl, komponente, wenn } from './lib.mjs';

// WordPress-IDs der EMMP-Komponenten (golfplatz.local; bei einer Migration mit Duplicator bleiben sie erhalten)
export const EMMP = { header: 71, nav: 69, dropdown: 68, menuItem: 67, toggle: 70 };

// Hauptnavigation laut docs/seitenstruktur.md › Header. Der letzte Punkt wird als Button dargestellt.
export const navigation = [
  {
    text: 'Golf spielen',
    kinder: [
      ['Platz & Bahnen', '/platz/'],
      ['Spielvorgaben', '/platz/spielvorgaben/'],
      ['Zählkarte', '/platz/zaehlkarte/'],
      ['Greenfee & Preise', '/greenfee/'],
      ['Turniere & Kalender', '/turniere/'],
      ['Golfschule', '/golfschule/'],
    ],
  },
  { text: 'Mitgliedschaft', link: '/mitgliedschaft/' },
  { text: 'Mannschaften', link: '/mannschaften/' },
  { text: 'Restaurant', link: '/restaurant/' },
  { text: 'Aktuelles', link: '/news/' },
  { text: 'Club & Kontakt', link: '/club/' },
  { text: 'Als Gast spielen', link: '/greenfee/#spielen' },
];

const menuItem = (text_, linkTo) =>
  emmp(EMMP.menuItem, { text: text_, linkTo, relocation: gruppe({ mode: 'none' }) }, { Content: '' });

const dropdown = (text_, kinder) =>
  emmp(
    EMMP.dropdown,
    {
      text: text_,
      nestedDropdown: gruppe({ parentRelative: '{false}', excludeEqualHeight: '{false}', equalHeights: '{false}' }),
      general: gruppe({ appearance: 'default' }),
      dropdownTriggerMode: 'both',
      linkParentItem: '{false}',
    },
    { Nested_Dropdown_Content: kinder.map(([t_, l]) => menuItem(t_, l)), Mega_Menu_Content: '' },
  );

// Clublogo: Bild aus den Clubdaten (club_logo, im dunklen Farbschema club_logo_hell); ohne Bild Fahnen-Signet.
// Schriftzug („Name im Logo“, Ort · Region) ohne Logo immer, mit Logo nur bei „Schriftzug neben dem Logo“.
// alt leer: der Link trägt den Clubnamen (aria-label).
const logoBild = (quelle, zusatz = '') => el('img', 'site-logo__bild' + zusatz, [], { attrs: { src: `{options.golfplatz.club.${quelle}}`, alt: '' } });
const logo = () =>
  el('a', 'dwc-nest-menu__logo site-logo', [
    wenn('options.golfplatz.club.hat_logo', [
      wenn('options.golfplatz.club.hat_logo_hell', [logoBild('logo_hell', ' site-logo__bild--hell')]),
      logoBild('logo'),
    ]),
    wenn('options.golfplatz.club.hat_logo', [
      el('svg', 'site-logo__mark', [
        svgEl('circle', { cx: 24, cy: 24, r: 22.5, fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }),
        svgEl('circle', { cx: 24, cy: 24, r: 19, fill: 'none', stroke: 'currentColor', 'stroke-width': 0.6 }),
        svgEl('path', { d: 'M21 34V12l11 5-9 4', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.8, 'stroke-linejoin': 'round' }),
        svgEl('path', { d: 'M12 35c5-3 19-3 24 0', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.4 }),
      ], { attrs: { viewBox: '0 0 48 48', 'aria-hidden': 'true' } }),
    ], 'isFalsy'),
    wenn('options.golfplatz.club.zeige_schriftzug', [
      el('span', 'site-logo__text', [
        t('span', 'site-logo__name', '{options.golfplatz.club.logoname}'),
        t('span', 'site-logo__since', '{options.golfplatz.club.unterzeile}'),
      ]),
    ]),
  ], { attrs: { href: '/', 'aria-label': club('club_name'), 'data-breakout': '' }, name: 'Logo', styles: ['pk20gtg'] });

// Top-Bar: Platzstatus-Ampel, Telefon, Mitglieder-Login
const topBar = () =>
  el('div', 'top-bar', [
    el('div', 'top-bar__inner container', [
      komponente('Ampel'),
      el('div', 'top-bar__links', [
        komponente('FarbschemaUmschalter'),
        el('a', 'top-bar__link', [icon('phone'), t('span', 'top-bar__link-text', club('club_telefon'))], { attrs: { href: telHref('club_telefon') } }),
        el('a', 'top-bar__link top-bar__link--login', [icon('user'), t('span', 'top-bar__link-text', 'Mitglieder-Login')], { attrs: { href: '/mitglieder/' } }),
      ]),
    ]),
  ], { name: 'Top-Bar' });

export const header = () =>
  emmp(
    EMMP.header,
    {
      sticky: gruppe({ stickyHeader: '{true}', specialStickyOverlayStyles: '{false}', scrollDownVisibility: 'Default', scrollUpVisibility: 'Default' }),
      overlay: gruppe({ overlayHeader: '{false}', overlayHeaderMobile: '{false}', offsetSectionPadding: '{false}' }),
      accessibilty: gruppe({ skipLink: 'true' }),
      darkBackgroundPreview: '{false}',
      mmProAiAssistant: '{false}',
      liquidGlass: gruppe({ enable: '{false}' }),
    },
    {
      default: [
        topBar(),
        el('div', 'dwc-nest-header', [
          el('div', 'dwc-nest-header__container', [
            logo(),
            emmp(
              EMMP.nav,
              {
                animation: gruppe({ stripeStyle: '{false}', adaptiveHeight: '{false}', animateAdaptiveContent: '{false}' }),
                menuMode: gruppe({ offcanvasMode: '{false}', flyoutOffcanvas: '{false}', flyoutOnHover: '{false}', lastItemIsButton: 'true', nonButtonItemsAlignment: 'Default' }),
                mobile: gruppe({ fullscreenMobileMenu: '{false}', removeMenuItemBorders: '{false}', transparentMobileTop: '{true}', slideInDirection: 'right', submenuReveal: 'slide', hideBackText: '{false}', backTextMode: 'back-to', backToHomeMenuText: 'Hauptmenü', previewMobileMenu: '{false}' }),
                interactionUx: gruppe({ nestedDropdownActiveOverlay: '{true}', menuItemHoverEffect: 'Default' }),
                backdrop: gruppe({ hideNavBackdrop: '{false}' }),
                logo: gruppe({ centeredLogo: '{false}', hideMobileLogoInFullscreenMode: '{false}', centerGuide: '{false}' }),
                buffer: gruppe({ previewBufferZone: '{false}' }),
                classes: gruppe({ stylingClasses: 'negtkxf' }),
                dropdown: gruppe({ blendOpenDropdowns: '{true}', caret: '{true}', arrowVisibilty: 'Default' }),
              },
              {
                Nav_items: navigation.map((p) => (p.kinder ? dropdown(p.text, p.kinder) : menuItem(p.text, p.link))),
                Mobile_Logo: '',
                MobileTop_Content: '',
              },
              'Hauptnavigation',
            ),
            emmp(EMMP.toggle, {
              ariaLabel: 'Menü öffnen',
              label: gruppe({ enable: '{true}', text: 'Open/Close', openText: 'Menü', closeText: 'Schließen', fontSize: '0.875rem', color: 'var(--primary)', gap: '0.5rem' }),
              appearance: gruppe({ flip: '{false}', toggleStyle: 'Default', equalize: '{false}', alwaysVisible: '{false}', pillShape: '{false}', hamburgerIcon: 'Default', color: 'var(--primary)', hoverColor: 'var(--secondary)' }),
            }, {}, 'Menü-Button'),
          ], { attrs: { 'data-etch-element': 'container' }, name: 'Header Container', styles: ['etch-container-style', 'duncscw'] }),
        ], { attrs: { 'data-allow-overlay-mobile-opacity': '' }, name: 'Header Inner Wrap', styles: ['vt38v59'] }),
      ],
    },
    'Header',
  );
