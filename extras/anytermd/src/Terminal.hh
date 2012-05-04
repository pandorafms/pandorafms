// common/Terminal.hh
// This file is part of AnyTerm; see http://anyterm.org/
// (C) 2006-2007 Philip Endecott

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

#ifndef Terminal_hh
#define Terminal_hh

#include <string>

#include "Cell.hh"
#include "Attributes.hh"
#include "Screen.hh"
#include "unicode.hh"
#include "Iconver.hh"


class Terminal {

public:
  Terminal(int rows, int cols, Screen& screen_);
  ~Terminal();

  int rows(void) const;
  int cols(void) const;

  Screen& screen;

  bool was_dirty(void);

  void write(ucs4_string data);

private:
  int rows_;
  int cols_;

  int saved_cursor_row;
  int saved_cursor_col;

  int scrolltop;
  int scrollbottom;

  Attributes current_attrs;

  enum charset_mode_t { cs_normal, cs_pc, cs_vt100gr };
  charset_mode_t charset_modes[2];
  int charset_mode;

  bool crlf_mode;

  bool dirty;

  int nparams;
  static const int nparams_max = 16;
  int params[nparams_max];

  enum esc_state_t { normal, seen_esc, seen_csi, seen_csi_private, seen_esclparen, seen_escrparen };
  esc_state_t esc_state;

  pbe::Iconver<pbe::permissive,char,ucs4_char> pc850_to_ucs4;

  void clip_cursor();
  void cursor_line_down();
  void cursor_line_up();
  void write_normal_char(ucs4_char c);
  void carriage_return();
  void line_feed();
  void backspace();
  void tab();
  void reset();
  void csi_SGR();
  void csi_SM();
  void csi_RM();
  void csi_DSR();
  void csi_ED();
  void csi_CUP();
  void csi_HVP();
  void csi_CUU();
  void csi_CUD();
  void csi_VPR();
  void csi_CUF();
  void csi_HPR();
  void csi_CUB();
  void csi_CNL();
  void csi_CPL();
  void csi_CHA();
  void csi_HPA();
  void csi_VPA();
  void csi_EL();
  void csi_ICH();
  void csi_DCH();
  void csi_IL();
  void csi_DL();
  void csi_ECH();
  void csi_DECSTBM();
  void csi_SAVECUR();
  void csi_RESTORECUR();
  void csi_DECSET();
  void csi_DECRST();
  void write_char(ucs4_char c);
};


#endif
