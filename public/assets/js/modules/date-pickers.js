function flowdeskPad(value) {
  return String(value).padStart(2, '0');
}

function flowdeskMonthPicker(initialValue = '') {
  const months = {
    '01': 'Jan',
    '02': 'Fev',
    '03': 'Mar',
    '04': 'Abr',
    '05': 'Mai',
    '06': 'Jun',
    '07': 'Jul',
    '08': 'Ago',
    '09': 'Set',
    '10': 'Out',
    '11': 'Nov',
    '12': 'Dez',
  };
  const now = new Date();
  const [initialYear, initialMonth] = (initialValue || '').split('-');
  const safeYear = Number(initialYear) || now.getFullYear();
  const safeMonth = months[initialMonth] ? initialMonth : flowdeskPad(now.getMonth() + 1);

  return {
    open: false,
    selectedValue: `${safeYear}-${safeMonth}`,
    displayYear: safeYear,
    toggle() {
      this.open = !this.open;
    },
    close() {
      this.open = false;
    },
    get selectedMonth() {
      return this.selectedValue.split('-')[1];
    },
    get selectedYear() {
      return Number(this.selectedValue.split('-')[0]);
    },
    get triggerLabel() {
      return `${months[this.selectedMonth]} ${this.selectedYear}`;
    },
    prevYear() {
      this.displayYear -= 1;
    },
    nextYear() {
      this.displayYear += 1;
    },
    monthButtonClass(month) {
      return {
        'is-selected': this.selectedMonth === month && this.selectedYear === this.displayYear,
        'is-current': month === flowdeskPad(now.getMonth() + 1) && this.displayYear === now.getFullYear(),
      };
    },
    selectMonth(month) {
      this.selectedValue = `${this.displayYear}-${month}`;
      this.submit();
    },
    resetToCurrent() {
      this.displayYear = now.getFullYear();
      this.selectedValue = `${now.getFullYear()}-${flowdeskPad(now.getMonth() + 1)}`;
      this.submit();
    },
    submit() {
      const form = this.$root.closest('form');
      if (form) {
        this.$nextTick(() => {
          form.submit();
        });
      }
      this.close();
    },
  };
}

function flowdeskFormatDate(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return '';
  }

  return `${date.getFullYear()}-${flowdeskPad(date.getMonth() + 1)}-${flowdeskPad(date.getDate())}`;
}

function flowdeskParseDate(value) {
  if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const [year, month, day] = value.split('-').map(Number);
  const date = new Date(year, month - 1, day);

  if (
    date.getFullYear() !== year ||
    date.getMonth() !== month - 1 ||
    date.getDate() !== day
  ) {
    return null;
  }

  return date;
}

function flowdeskDatePicker(initialValue = '', options = {}) {
  const today = new Date();
  const parsedInitial = flowdeskParseDate(initialValue);
  const selected = parsedInitial || (options.defaultToToday ? today : null);
  const viewDate = selected || today;

  return {
    open: false,
    selectedValue: selected ? flowdeskFormatDate(selected) : '',
    viewYear: viewDate.getFullYear(),
    viewMonth: viewDate.getMonth(),
    months: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
    weekdays: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
    toggle() {
      this.open = !this.open;
    },
    close() {
      this.open = false;
    },
    get triggerLabel() {
      if (!this.selectedValue) {
        return options.placeholder || 'Selecionar data';
      }

      const [year, month, day] = this.selectedValue.split('-');
      return `${day}/${month}/${year}`;
    },
    get headerLabel() {
      return `${this.months[this.viewMonth]} ${this.viewYear}`;
    },
    get selectedDate() {
      return flowdeskParseDate(this.selectedValue);
    },
    prevMonth() {
      if (this.viewMonth === 0) {
        this.viewMonth = 11;
        this.viewYear -= 1;
        return;
      }

      this.viewMonth -= 1;
    },
    nextMonth() {
      if (this.viewMonth === 11) {
        this.viewMonth = 0;
        this.viewYear += 1;
        return;
      }

      this.viewMonth += 1;
    },
    isToday(day) {
      return (
        today.getFullYear() === this.viewYear &&
        today.getMonth() === this.viewMonth &&
        today.getDate() === day
      );
    },
    isSelected(day) {
      const selectedDate = this.selectedDate;
      if (!selectedDate) return false;

      return (
        selectedDate.getFullYear() === this.viewYear &&
        selectedDate.getMonth() === this.viewMonth &&
        selectedDate.getDate() === day
      );
    },
    selectDay(day) {
      this.selectedValue = `${this.viewYear}-${flowdeskPad(this.viewMonth + 1)}-${flowdeskPad(day)}`;
      this.close();
    },
    clear() {
      this.selectedValue = '';
      this.close();
    },
    selectToday() {
      this.viewYear = today.getFullYear();
      this.viewMonth = today.getMonth();
      this.selectedValue = flowdeskFormatDate(today);
      this.close();
    },
    days() {
      const firstDay = new Date(this.viewYear, this.viewMonth, 1);
      const startWeekDay = firstDay.getDay();
      const totalDays = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
      const items = [];

      for (let i = 0; i < startWeekDay; i += 1) {
        items.push({ empty: true, key: `empty-${i}` });
      }

      for (let day = 1; day <= totalDays; day += 1) {
        items.push({
          empty: false,
          day,
          key: `day-${day}`,
        });
      }

      return items;
    },
  };
}

window.flowdeskDatePicker = flowdeskDatePicker;
window.flowdeskMonthPicker = flowdeskMonthPicker;
