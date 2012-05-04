// daemon/Screen.hh
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

#ifndef Screen_hh
#define Screen_hh

#include <vector>
#include <algorithm>

#include "Cell.hh"


class Screen {
  int rows_;
  int cols_;
  int scrollback_;
  typedef std::vector<Cell> row_t;
  typedef std::vector<row_t*> cells_t;
  cells_t cells;
  unsigned int wrap;

public:
  Screen(int rows, int cols, int scrollback=0):
    rows_(rows), cols_(cols), scrollback_(scrollback),
    wrap(0),
    cursor_row(0), cursor_col(0), cursor_visible(true)
  {
    for (int r=0; r<scrollback; ++r) {
      cells.push_back(new row_t(cols));  // leaks if it throws
    }
    for (int r=0; r<rows; ++r) {
      cells.push_back(new row_t(cols));  // leaks if it throws
    }
  }

  ~Screen() {
    for (cells_t::iterator i = cells.begin();
         i != cells.end(); ++i) {
      delete *i;
    }
  }

  int rows() const {
    return rows_;
  }

  int cols() const {
    return cols_;
  }

  int scrollback() const {
    return scrollback_;
  }

  Cell& operator()(int r, int c)
  {
    return (*cells[row_idx(r)])[c];
  }

  const Cell& operator()(int r, int c) const
  {
    return (*cells[row_idx(r)])[c];
  }

  void scroll_down(int top, int bottom, int n=1)
  {
    // If we're asked to scroll the whole visible screen down, we scroll
    // into the scrollback region.  Otherwise, the scrollback region is
    // not changed.
    if (top==0 && bottom==rows()-1) {
      wrap = (wrap+n)%(rows()+scrollback());
    } else {
      normalise_wrap();
      std::rotate(cells.begin()+scrollback()+top, cells.begin()+scrollback()+top+n, 
                  cells.begin()+scrollback()+bottom+1);
    }
    for (int r=bottom+1-n; r<=bottom; ++r) {
      clear_row(r);
    }
  }

  void scroll_up(int top, int bottom, int n=1)
  {
    // Never touch the scrollback region.
    if (scrollback()==0 && top==0 && bottom==rows()-1) {
      wrap = (wrap-n)%(rows()+scrollback());
    } else {
      normalise_wrap();
      std::rotate(cells.begin()+scrollback()+top, cells.begin()+scrollback()+bottom+1-n,
                  cells.begin()+scrollback()+bottom+1);
    }
    for (int r=top; r<top+n; ++r) {
      clear_row(r);
    }
  }

  int cursor_row;
  int cursor_col;
  bool cursor_visible;


private:
  int row_idx(int r) const {
    return (r+scrollback()+wrap)%(rows()+scrollback());
  }

  void clear_row(int r) {
    row_t& row = *(cells[row_idx(r)]);
    for (int c=0; c<cols(); ++c) {
      row[c] = Cell();   // FIXME this should probably use the terminal's current attributes 
                         // (e.g. background colour)
    }
  }

  void normalise_wrap() {
    if (wrap==0) {
      return;
    }
    std::rotate(cells.begin(), cells.begin()+wrap, cells.end());
    wrap=0;
  }
};


#endif
