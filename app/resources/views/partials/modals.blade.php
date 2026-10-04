<template x-if="modal">
  <div class="overlay" @click.self="close()">
    <div class="modal" role="dialog" aria-modal="true" :aria-label="title">
      <div class="modal-head">
        <div class="modal-title" x-text="title"></div>
        <button type="button" class="link-btn modal-x" @click="close()" aria-label="Zatvori">×</button>
      </div>

      <form method="POST" :action="action" class="fields" @input="err = ''">
        @csrf
        <input type="hidden" name="_modal" :value="modal">

        <template x-if="modal === 'invoice'">
          <div class="fields">
            <label class="field">Dobavljač
              <select name="supplier_id" x-model="f.supplier_id" @change="f.term_days = terms[f.supplier_id] ?? defTerm">
                @foreach ($supplierOptions as $a)
                  <option value="{{ $a->supplier->id }}">{{ $a->supplier->name }}</option>
                @endforeach
              </select>
            </label>
            <div class="row2">
              <label class="field">Broj fakture
                <input name="number" x-model="f.number" placeholder="npr. 1045/26" autocomplete="off">
              </label>
              <label class="field">Datum
                <input type="date" name="issued_on" x-model="f.issued_on">
              </label>
            </div>
            <div class="row2 wide">
              <label class="field">Iznos (RSD)
                <input name="amount" inputmode="decimal" x-model="f.amount" placeholder="0,00" autocomplete="off">
              </label>
              <label class="field">Valuta (dana)
                <input type="number" name="term_days" min="0" max="365" x-model="f.term_days">
              </label>
            </div>
            <div class="due-preview">
              <div>Rok plaćanja</div>
              <div x-text="duePreview"></div>
            </div>
          </div>
        </template>

        <template x-if="modal === 'payment'">
          <div class="fields">
            <input type="hidden" name="_pay" :value="JSON.stringify(pay)">
            <div class="pay-info">
              <div style="font-weight:700"><span x-text="pay.sup"></span> · br. <span x-text="pay.no"></span></div>
              <div>Iznos <span x-text="pay.amt"></span> · preostalo <b x-text="pay.rem"></b></div>
            </div>
            <div class="row2">
              <label class="field">Datum uplate
                <input type="date" name="paid_on" x-model="f.paid_on">
              </label>
              <label class="field">Iznos (RSD)
                <input name="amount" inputmode="decimal" x-model="f.amount" autocomplete="off">
              </label>
            </div>
            <button type="button" class="link-btn fill-full" @click="f.amount = pay.remNum; err = ''">Uplati ceo preostali iznos</button>
          </div>
        </template>

        <template x-if="modal === 'supplier'">
          <div class="fields">
            <label class="field">Naziv
              <input name="name" x-model="f.name" placeholder="npr. Agrofina" autocomplete="off">
            </label>
            <div class="row2 wide">
              <label class="field">PIB
                <input name="pib" x-model="f.pib" inputmode="numeric" autocomplete="off">
              </label>
              <label class="field">Valuta (dana)
                <input type="number" name="term_days" min="0" max="365" x-model="f.term_days">
              </label>
            </div>
          </div>
        </template>

        <template x-if="modal === 'contact'">
          <div class="fields">
            <label class="field">Ime i prezime
              <input name="name" x-model="f.name" autocomplete="off">
            </label>
            <label class="field">Uloga
              <input name="role" x-model="f.role" placeholder="Komercijalista">
            </label>
            <label class="field">Telefon
              <input type="tel" name="phone" x-model="f.phone" placeholder="06x xxx xxxx">
            </label>
          </div>
        </template>

        <div class="form-err" x-show="err" x-text="err"></div>
        <div class="modal-actions">
          <button type="button" class="btn btn-ghost btn-xl" @click="close()">Otkaži</button>
          <button type="submit" class="btn btn-primary btn-xl" style="padding:0 20px">Sačuvaj</button>
        </div>
      </form>
    </div>
  </div>
</template>
