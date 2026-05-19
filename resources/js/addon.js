import Note from './components/fieldtypes/Note.vue'

Statamic.booting(() => {
    Statamic.component('note-fieldtype', Note);
    Statamic.$conditions.add('showCascadingNote', ({ values }) => {
        return values.cep_ancestor_tickets;
    });
    Statamic.$conditions.add('showAccessTickets', ({ values }) => {
        return ! values.cep_ancestor_tickets?.startsWith("Protected by");
    });
})
