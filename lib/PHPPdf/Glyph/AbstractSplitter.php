<?php

namespace PHPPdf\Glyph;

/**
 * Splitter is able to split glyphs into specified glyph as parent.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class AbstractSplitter
{
    private $glyph;
    protected $totalVerticalTranslation = 0;

    public function __construct(Glyph $glyph)
    {
        $this->glyph = $glyph;
    }

    /**
     * @return Glyph
     */
    protected function getSubjectOfSplitting()
    {
        return $this->glyph;
    }

    public function split()
    {
        foreach($this->glyph->getChildren() as $child)
        {
            $this->splitChildIfNecessary($child);
        }
    }

    private function splitChildIfNecessary(Glyph $glyph)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        $glyph->translate(0, -$this->totalVerticalTranslation);

        if($this->shouldParentBeAutomaticallyBroken($glyph))
        {
            $pageYCoordEnd = $glyph->getDiagonalPoint()->getY() + 1;
        }
        else
        {
            $pageYCoordEnd = $this->glyph->getDiagonalPoint()->getY();
        }

        do
        {
            if($this->shouldBeSplited($glyph, $pageYCoordEnd))
            {
                $glyph = $this->splitChildAndGetProductOfSplitting($glyph);
                $childHasBeenSplitted = true;
            }
            else
            {
                if(!$childHasBeenSplitted)
                {
                    $this->addToSubjectOfSplitting($glyph);
                }

                $childMayBeSplitted = false;
            }
        }
        while($childMayBeSplitted);
    }

    /**
     * @return boolean
     */
    abstract protected function shouldParentBeAutomaticallyBroken(Glyph $glyph);

    private function shouldBeSplited(Glyph $glyph, $pageYCoordEnd)
    {
        $yEnd = $glyph->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    private function splitChildAndGetProductOfSplitting(Glyph $glyph)
    {
        $originalHeight = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();
        $glyphYCoordStart = $this->getChildYCoordOfFirstPoint($glyph);
        $splitLine = $glyphYCoordStart - $this->glyph->getDiagonalPoint()->getY();
        $splittedGlyph = $glyph->split($splitLine);

        $heightAfterSplit = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();

        $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = 0;

        if($splittedGlyph)
        {
            $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = $glyph->getDiagonalPoint()->getY() - $this->glyph->getDiagonalPoint()->getY();
            $glyphYCoordStart = $splittedGlyph->getFirstPoint()->getY();
            $this->addToSubjectOfSplitting($glyph);
            $heightAfterSplit += $splittedGlyph->getFirstPoint()->getY() - $splittedGlyph->getDiagonalPoint()->getY();
            $glyph = $splittedGlyph;
        }

        $translation = $this->getGlyphTranslation($glyph, $glyphYCoordStart);

        $this->breakSubjectOfSplittingIncraseTranslation($translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage);
        $this->addChildrenToCurrentPageAndTranslate($glyph, $translation);

        return $glyph;
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }

    private function getGlyphTranslation(Glyph $glyph, $glyphYCoordStart)
    {
        $translation = $this->glyph->getHeight() + $this->glyph->getMarginBottom() - $glyphYCoordStart;

        return $translation;
    }

    private function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $this->glyph->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }

    abstract protected function addToSubjectOfSplitting(Glyph $glyph);

    abstract protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation);
}