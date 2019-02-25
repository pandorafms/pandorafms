import { parseIntOr } from "./lib";

describe("function parseIntOr", () => {
  it("should retrieve valid int or a default value", () => {
    expect(parseIntOr("Foo", null)).toBe(null);
    expect(parseIntOr("1a", null)).toBe(1);
    expect(parseIntOr("a1", null)).toBe(null);
    expect(parseIntOr("1", null)).toBe(1);
    expect(parseIntOr(false, null)).toBe(null);
    expect(parseIntOr(true, null)).toBe(null);
    expect(parseIntOr(1, null)).toBe(1);
  });
});
