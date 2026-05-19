import Note from './components/fieldtypes/Note.vue'

Statamic.booting(() => {
    Statamic.component('note-fieldtype', Note)
})
