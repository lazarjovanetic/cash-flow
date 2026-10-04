// Stanje modala za unos (faktura, uplata, dobavljač, kontakt) i pomoćne funkcije stranica.
document.addEventListener('alpine:init', () => {
  const pad = n => String(n).padStart(2, '0');
  const addDays = (iso, n) => {
    const [y, m, d] = iso.split('-').map(Number);
    const dt = new Date(y, m - 1, d + Number(n));
    return `${pad(dt.getDate())}.${pad(dt.getMonth() + 1)}.${dt.getFullYear()}`;
  };
  Alpine.data('dugovanja', cfg => ({
    today: cfg.today,
    defTerm: cfg.defTerm,
    terms: cfg.terms,
    curSupplier: cfg.curSupplier,
    modal: cfg.modal || null,
    f: cfg.old || {},
    err: cfg.err || '',
    pay: cfg.pay || {},

    // Ekran na kasi stoji otvoren danima: osvežava se svakih 10 minuta i posle ponoći,
    // ali nikad dok je otvoren prozor za unos ili dok se kuca u polje (npr. pretraga).
    init() {
      const localDay = () => new Date().toDateString();
      const loadedOn = localDay();
      const busy = () => this.modal || ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName);
      let last = Date.now();
      setInterval(() => {
        if (busy()) return;
        if (Date.now() - last >= 10 * 60 * 1000 || localDay() !== loadedOn) location.reload();
      }, 30 * 1000);
      document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && localDay() !== loadedOn && !busy()) location.reload(); });
      // Svaka akcija korisnika pomera rok, da se stranica ne osveži usred rada.
      ['click', 'keydown'].forEach(ev => document.addEventListener(ev, () => { last = Date.now(); }, { passive: true }));
    },

    get title() {
      return { invoice: 'Nova faktura', payment: 'Unos uplate', supplier: 'Novi dobavljač', contact: 'Novi kontakt' }[this.modal] || '';
    },
    get action() {
      return this.modal === 'payment' ? this.pay.url : cfg.routes[this.modal];
    },
    get duePreview() {
      const t = this.f.term_days;
      return this.f.issued_on && t !== '' && t != null && Number(t) >= 0 ? addDays(this.f.issued_on, t) : '—';
    },

    show(modal, form) { this.modal = modal; this.f = form; this.err = ''; },
    close() { this.modal = null; },
    openInvoice(sid) {
      sid = sid || cfg.firstSupplier || '';
      this.show('invoice', { supplier_id: String(sid), number: '', issued_on: this.today, amount: '', term_days: this.terms[sid] ?? this.defTerm });
    },
    openPayment(pay) { this.pay = pay; this.show('payment', { paid_on: this.today, amount: pay.remNum }); },
    openSupplier() { this.show('supplier', { name: '', pib: '', term_days: this.defTerm }); },
    openContact() { this.show('contact', { name: '', role: 'Komercijalista', phone: '' }); },
  }));

  // Pregled: filter po statusu i pretraga dobavljača.
  Alpine.data('home', tiles => ({
    filter: 'svi',
    q: '',
    match(t) {
      const q = this.q.trim().toLowerCase();
      return (this.filter === 'svi' || t.status === this.filter) && (!q || t.name.toLowerCase().includes(q));
    },
    get none() { return !tiles.some(t => this.match(t)); },
  }));
});
