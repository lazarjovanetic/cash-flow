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
